<?php

namespace App\Services;

use App\Enums\MediaType;
use App\Enums\MovieStatus;
use App\Models\Movie;
use App\Models\User;
use App\Models\UserMovie;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RecommendationService
{
    public function __construct(private readonly TmdbService $tmdb) {}

    /**
     * Compute genre affinity scores based on rated watched movies.
     * Returns genre_id => weighted_score (0-1).
     *
     * @return array<int, float>
     */
    public function genreAffinities(User $user): array
    {
        $watched = UserMovie::forUser($user->id)
            ->withStatus(MovieStatus::Watched)
            ->whereNotNull('rating')
            ->with('movie')
            ->get();

        if ($watched->isEmpty()) {
            return [];
        }

        $genreScores = [];
        $genreCounts = [];

        foreach ($watched as $userMovie) {
            $genreIds = $userMovie->movie?->genre_ids ?? [];
            $normalizedRating = ($userMovie->rating / 10); // 0-1

            foreach ($genreIds as $genreId) {
                $genreScores[$genreId] = ($genreScores[$genreId] ?? 0) + $normalizedRating;
                $genreCounts[$genreId] = ($genreCounts[$genreId] ?? 0) + 1;
            }
        }

        $affinities = [];
        foreach ($genreScores as $genreId => $totalScore) {
            $affinities[$genreId] = $totalScore / $genreCounts[$genreId];
        }

        arsort($affinities);

        return $affinities;
    }

    /**
     * Compute a heuristic recommendation score for a movie given a user's affinities.
     * Returns a score between 0 and 100.
     *
     * @param  array<int, float>  $affinities
     */
    public function scoreMovie(Movie $movie, array $affinities, float $avgRating): float
    {
        // Component 1: TMDB score (0-10 normalized to 0-40 points)
        $tmdbScore = ($movie->vote_average / 10) * 40;

        // Component 2: Genre affinity (0-40 points)
        $genreIds = $movie->genre_ids ?? [];
        $genreAffinityScore = 0;

        if (! empty($genreIds) && ! empty($affinities)) {
            $matchingAffinities = array_intersect_key($affinities, array_flip($genreIds));
            if (! empty($matchingAffinities)) {
                $genreAffinityScore = (array_sum($matchingAffinities) / count($matchingAffinities)) * 40;
            }
        }

        // Component 3: Popularity boost (0-10 points, log-scaled)
        $popularityScore = min(10, log10(max(1, $movie->popularity)) * 3);

        // Component 4: Vote count confidence (0-10 points)
        $voteConfidence = min(10, log10(max(1, $movie->vote_count)) * 2);

        $total = $tmdbScore + $genreAffinityScore + $popularityScore + $voteConfidence;

        return round(min(100, $total), 1);
    }

    /**
     * Get personalized recommendations for a user.
     *
     * @return Collection<int, array{movie: Movie, score: float}>
     */
    public function getPersonalizedRecommendations(User $user, int $limit = 20): Collection
    {
        $cacheKey = "recommendations.user.{$user->id}";

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($user, $limit) {
            $affinities = $this->genreAffinities($user);

            // Get all watched/watchlist/dismissed movie IDs to exclude
            $excludedTmdbIds = UserMovie::forUser($user->id)
                ->with('movie')
                ->get()
                ->pluck('movie.tmdb_id')
                ->filter()
                ->toArray();

            // Get average rating to calibrate scores
            $avgRating = UserMovie::forUser($user->id)
                ->withStatus(MovieStatus::Watched)
                ->whereNotNull('rating')
                ->avg('rating') ?? 6.0;

            // Pull candidates: popular movies from DB + from TMDB if needed
            $candidates = Movie::whereNotIn('tmdb_id', $excludedTmdbIds)
                ->where('vote_count', '>', 100)
                ->orderByDesc('popularity')
                ->limit(200)
                ->get();

            // If no affinities, fallback to TMDB score only
            if (empty($affinities)) {
                return $candidates
                    ->sortByDesc('vote_average')
                    ->take($limit)
                    ->map(fn ($movie) => [
                        'movie' => $movie,
                        'score' => round($movie->vote_average * 10, 1),
                    ])
                    ->values();
            }

            return $candidates
                ->map(fn ($movie) => [
                    'movie' => $movie,
                    'score' => $this->scoreMovie($movie, $affinities, (float) $avgRating),
                ])
                ->sortByDesc('score')
                ->take($limit)
                ->values();
        });
    }

    /**
     * Get top preferred genres for a user (sorted by affinity).
     *
     * @return array<int, string>
     */
    public function topGenres(User $user, int $limit = 5): array
    {
        $affinities = $this->genreAffinities($user);
        $topIds = array_slice(array_keys($affinities), 0, $limit);

        return $this->tmdb->genreNamesFromIds($topIds);
    }

    /**
     * Get statistics summary for a user.
     *
     * @return array{total_watched: int, total_watchlist: int, total_proposed: int, avg_rating: float|null, top_genres: array<string>}
     */
    public function userStats(User $user): array
    {
        $userMovies = UserMovie::forUser($user->id)->get();

        return [
            'total_watched' => $userMovies->where('status', MovieStatus::Watched)->count(),
            'total_watchlist' => $userMovies->where('status', MovieStatus::Watchlist)->count(),
            'total_proposed' => $userMovies->where('status', MovieStatus::Proposed)->count(),
            'avg_rating' => $userMovies->where('status', MovieStatus::Watched)->whereNotNull('rating')->avg('rating'),
            'top_genres' => $this->topGenres($user),
        ];
    }

    /**
     * Invalidate cached recommendations for a user.
     */
    public function invalidateCache(User $user): void
    {
        Cache::forget("recommendations.user.{$user->id}");
    }
}
