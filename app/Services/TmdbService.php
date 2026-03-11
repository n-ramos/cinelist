<?php

namespace App\Services;

use App\Enums\MediaType;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmdbService
{
    private string $baseUrl = 'https://api.themoviedb.org/3';

    private string $imageBaseUrl = 'https://image.tmdb.org/t/p';

    public function __construct(private readonly string $apiKey) {}

    /**
     * Search movies and TV shows.
     *
     * @return array{results: array<int, array<string, mixed>>, total_pages: int, total_results: int}
     */
    public function search(string $query, int $page = 1): array
    {
        $cacheKey = "tmdb.search.{$query}.{$page}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($query, $page) {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/search/multi", [
                    'query' => $query,
                    'page' => $page,
                    'language' => 'fr-FR',
                    'include_adult' => false,
                ]);

            if ($response->failed()) {
                Log::warning('TMDB search failed', ['query' => $query, 'status' => $response->status()]);

                return ['results' => [], 'total_pages' => 0, 'total_results' => 0];
            }

            $data = $response->json();
            $data['results'] = array_filter(
                $data['results'] ?? [],
                fn ($item) => in_array($item['media_type'] ?? '', ['movie', 'tv'])
            );

            return $data;
        });
    }

    /**
     * Get trending movies/TV shows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function trending(string $timeWindow = 'week', int $page = 1): array
    {
        $cacheKey = "tmdb.trending.{$timeWindow}.{$page}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($timeWindow, $page) {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/trending/all/{$timeWindow}", [
                    'page' => $page,
                    'language' => 'fr-FR',
                ]);

            if ($response->failed()) {
                return [];
            }

            return array_filter(
                $response->json('results', []),
                fn ($item) => in_array($item['media_type'] ?? '', ['movie', 'tv'])
            );
        });
    }

    /**
     * Discover movies with filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array{results: array<int, array<string, mixed>>, total_pages: int, total_results: int}
     */
    public function discover(MediaType $type, array $filters = [], int $page = 1): array
    {
        $cacheKey = 'tmdb.discover.' . $type->value . '.' . md5(serialize($filters)) . ".{$page}";

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($type, $filters, $page) {
            $params = array_merge([
                'page' => $page,
                'language' => 'fr-FR',
                'sort_by' => 'popularity.desc',
                'include_adult' => false,
            ], $filters);

            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/discover/{$type->tmdbEndpoint()}", $params);

            if ($response->failed()) {
                return ['results' => [], 'total_pages' => 0, 'total_results' => 0];
            }

            return $response->json();
        });
    }

    /**
     * Get full details for a movie or TV show and upsert in DB.
     */
    public function getDetails(int $tmdbId, MediaType $type): ?Movie
    {
        $existing = Movie::where('tmdb_id', $tmdbId)->first();

        if ($existing && $existing->cached_at && $existing->cached_at->gt(now()->subDays(3))) {
            return $existing;
        }

        $endpoint = $type->tmdbEndpoint();
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/{$endpoint}/{$tmdbId}", [
                'language' => 'fr-FR',
                'append_to_response' => 'credits,videos,similar,recommendations',
            ]);

        if ($response->failed()) {
            return $existing;
        }

        $data = $response->json();

        return Movie::updateOrCreate(
            ['tmdb_id' => $tmdbId],
            $this->mapToMovieAttributes($data, $type)
        );
    }

    /**
     * Get recommendations for a movie/TV show.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecommendations(int $tmdbId, MediaType $type): array
    {
        $cacheKey = "tmdb.recommendations.{$type->value}.{$tmdbId}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($tmdbId, $type) {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/{$type->tmdbEndpoint()}/{$tmdbId}/recommendations", [
                    'language' => 'fr-FR',
                ]);

            if ($response->failed()) {
                return [];
            }

            return $response->json('results', []);
        });
    }

    /**
     * Fetch and sync genres for a media type.
     */
    public function syncGenres(MediaType $type): void
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/genre/{$type->tmdbEndpoint()}/list", [
                'language' => 'fr-FR',
            ]);

        if ($response->failed()) {
            return;
        }

        foreach ($response->json('genres', []) as $genre) {
            Genre::updateOrCreate(
                ['tmdb_id' => $genre['id'], 'type' => $type->value],
                ['name' => $genre['name']]
            );
        }
    }

    /**
     * Get genre names from IDs.
     *
     * @param  array<int>  $genreIds
     * @return array<string>
     */
    public function genreNamesFromIds(array $genreIds): array
    {
        $genres = Genre::whereIn('tmdb_id', $genreIds)->pluck('name', 'tmdb_id');

        return array_values(
            array_map(fn ($id) => $genres[$id] ?? '', $genreIds)
        );
    }

    /**
     * Upsert a raw TMDB result array (from search/trending) as a Movie.
     *
     * @param  array<string, mixed>  $data
     */
    public function upsertFromRaw(array $data): ?Movie
    {
        $type = MediaType::tryFrom($data['media_type'] ?? 'movie') ?? MediaType::Movie;

        if (! isset($data['id'])) {
            return null;
        }

        return Movie::updateOrCreate(
            ['tmdb_id' => $data['id']],
            $this->mapRawToAttributes($data, $type)
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapToMovieAttributes(array $data, MediaType $type): array
    {
        $title = $type === MediaType::Movie
            ? ($data['title'] ?? $data['original_title'] ?? 'Unknown')
            : ($data['name'] ?? $data['original_name'] ?? 'Unknown');

        $originalTitle = $type === MediaType::Movie
            ? ($data['original_title'] ?? null)
            : ($data['original_name'] ?? null);

        $releaseDate = $type === MediaType::Movie
            ? ($data['release_date'] ?? null)
            : ($data['first_air_date'] ?? null);

        return [
            'type' => $type->value,
            'title' => $title,
            'original_title' => $originalTitle,
            'overview' => $data['overview'] ?? null,
            'poster_path' => $data['poster_path'] ?? null,
            'backdrop_path' => $data['backdrop_path'] ?? null,
            'release_date' => $releaseDate ?: null,
            'vote_average' => $data['vote_average'] ?? 0,
            'vote_count' => $data['vote_count'] ?? 0,
            'genre_ids' => array_column($data['genres'] ?? [], 'id'),
            'runtime' => $type === MediaType::Movie
                ? ($data['runtime'] ?? null)
                : ($data['episode_run_time'][0] ?? null),
            'original_language' => $data['original_language'] ?? null,
            'popularity' => $data['popularity'] ?? 0,
            'raw_data' => $data,
            'cached_at' => now(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapRawToAttributes(array $data, MediaType $type): array
    {
        $title = $type === MediaType::Movie
            ? ($data['title'] ?? $data['original_title'] ?? 'Unknown')
            : ($data['name'] ?? $data['original_name'] ?? 'Unknown');

        $releaseDate = $type === MediaType::Movie
            ? ($data['release_date'] ?? null)
            : ($data['first_air_date'] ?? null);

        return [
            'type' => $type->value,
            'title' => $title,
            'original_title' => $type === MediaType::Movie ? ($data['original_title'] ?? null) : ($data['original_name'] ?? null),
            'overview' => $data['overview'] ?? null,
            'poster_path' => $data['poster_path'] ?? null,
            'backdrop_path' => $data['backdrop_path'] ?? null,
            'release_date' => $releaseDate ?: null,
            'vote_average' => $data['vote_average'] ?? 0,
            'vote_count' => $data['vote_count'] ?? 0,
            'genre_ids' => $data['genre_ids'] ?? [],
            'original_language' => $data['original_language'] ?? null,
            'popularity' => $data['popularity'] ?? 0,
            'cached_at' => now(),
        ];
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return [
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept' => 'application/json',
        ];
    }
}
