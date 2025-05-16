<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemKeyword;
use App\Models\RecommendationConfiguration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class RecommendationController extends Controller
{
    private $configurations = null;
    private $allKeywordsCache = [];

    public function __construct()
    {
        $this->loadConfigurationsFromCache();
    }

    private function loadConfigurationsFromCache(): void
    {
        if ($this->configurations === null) {
            $this->configurations = Cache::remember('recommendation_configs', 3600, function () {
                return RecommendationConfiguration::pluck('parameter_value', 'parameter_name')->all();
            });
            if (empty($this->configurations)) {
                Log::warning('Recommendation configurations not found in cache or database, using defaults.');
                $this->configurations = $this->getDefaultConfigurations();
            }
        }
    }

    private function getDefaultConfigurations(): array
    {
        return [
            'content_based_overall_weight'    => '0.3',
            'case_based_overall_weight'       => '0.7',
            'filter_content_based_weight'     => '0.4',
            'filter_case_based_weight'        => '0.6',
            'cb_attr_price_weight'            => '0.30',
            'cb_attr_price_max_value'         => '15000000',
            'cb_attr_btu_capacity_weight'     => '0.25',
            'cb_attr_btu_capacity_max_value'  => '24000',
            'cb_attr_power_consumption_watt_weight' => '0.15',
            'cb_attr_power_consumption_watt_max_value' => '2500',
            'cb_attr_is_inverter_weight'      => '0.10',
            'cb_is_inverter_max_value'        => '1',
            'cb_attr_room_size_weight'        => '0.10',
            'cb_attr_room_size_max_value'     => '30',
            'cb_attr_warranty_weight'         => '0.10',
            'cb_attr_warranty_max_value'      => '10',
        ];
    }
    public function areConfigurationsLoaded(): bool
    {
        return !empty($this->configurations);
    }
    public function getLoadedConfigurations(): ?array
    {
        return $this->configurations;
    }

    public function getRecommendations(Item $queryItem, int $limit = 4): array
    {
        if (!$this->areConfigurationsLoaded()) {
            Log::error('Recommendation configurations are not loaded in getRecommendations.');
            return [];
        }
        $candidateItems = Item::where('id', '!=', $queryItem->id)
            ->where('status', 'available')
            ->where('stock', '>', 0)
            ->whereNotNull('description')
            ->where(function ($q) use ($queryItem) {
                if ($queryItem->category_id) {
                    $q->where('category_id', $queryItem->category_id);
                }
                if ($queryItem->brand_id) {
                    $q->orWhere('brand_id', $queryItem->brand_id);
                }
            })
            ->inRandomOrder()
            ->take(100)
            ->get();

        if ($candidateItems->isEmpty()) {
            return [];
        }

        $itemIdsToLoad = $candidateItems->pluck('id')->push($queryItem->id)->unique();
        $this->preloadItemKeywords($itemIdsToLoad);

        $recommendationsWithScores = [];
        foreach ($candidateItems as $candidate) {
            $contentScore = $this->calculateContentBasedSimilarity($queryItem, $candidate);
            $caseScore = $this->calculateCaseBasedSimilarity($queryItem, $candidate);

            $hybridScore = $this->calculateHybridScore(
                $contentScore,
                $caseScore,
                (float)($this->configurations['content_based_overall_weight'] ?? 0.3),
                (float)($this->configurations['case_based_overall_weight'] ?? 0.7)
            );

            if ($hybridScore > 0.01) {
                $recommendationsWithScores[$candidate->id] = $hybridScore;
            }
        }

        if (empty($recommendationsWithScores)) {
            return [];
        }

        arsort($recommendationsWithScores);
        $topItemIds = array_slice(array_keys($recommendationsWithScores), 0, $limit);

        if (empty($topItemIds)) {
            return [];
        }

        $itemsMap = Item::with(['category', 'brand'])->whereIn('id', $topItemIds)->get()->keyBy('id');

        $finalRecommendations = [];
        foreach ($topItemIds as $itemId) {
            if (isset($itemsMap[$itemId])) {
                $finalRecommendations[] = ['item' => $itemsMap[$itemId], 'score' => $recommendationsWithScores[$itemId]];
            }
        }
        usort($finalRecommendations, fn($a, $b) => $b['score'] <=> $a['score']);
        return $finalRecommendations;
    }

    public function rankItemsByProfile(Item $idealProfileItem, EloquentCollection $candidateItems, int $limit = 20): array
    {
        if (!$this->areConfigurationsLoaded()) {
            Log::error('Recommendation configurations are not loaded in rankItemsByProfile.');
            return [];
        }
        if ($candidateItems->isEmpty()) {
            return [];
        }

        $itemIdsToLoad = $candidateItems->pluck('id')->unique();
        $this->preloadItemKeywords($itemIdsToLoad);

        $idealKeywords = $this->extractKeywordsFromItemProfile($idealProfileItem);

        $rankedResultsWithScores = [];
        foreach ($candidateItems as $candidate) {
            $contentScore = 0.0;
            if (!empty($idealKeywords)) {
                $candidateKeywords = $this->getItemKeywords($candidate->id);
                if (!empty($candidateKeywords)) {
                    $intersection = count(array_intersect($idealKeywords, $candidateKeywords));
                    $denominator = count($idealKeywords) + count($candidateKeywords);
                    $contentScore = ($denominator == 0) ? 0.0 : (2.0 * $intersection) / $denominator;
                }
            }

            $caseScore = $this->calculateCaseBasedSimilarity($idealProfileItem, $candidate);

            $hybridScore = $this->calculateHybridScore(
                $contentScore,
                $caseScore,
                (float)($this->configurations['filter_content_based_weight'] ?? 0.1),
                (float)($this->configurations['filter_case_based_weight'] ?? 0.9)
            );

            if ($hybridScore > 0.0001) {
                $rankedResultsWithScores[$candidate->id] = $hybridScore;
            }
        }

        if (empty($rankedResultsWithScores)) {
            return [];
        }

        arsort($rankedResultsWithScores);
        $topItemIds = array_slice(array_keys($rankedResultsWithScores), 0, $limit);

        if (empty($topItemIds)) {
            return [];
        }

        $itemsMap = $candidateItems->keyBy('id');

        $finalRankedItems = [];
        foreach ($topItemIds as $itemId) {
            if (isset($itemsMap[$itemId])) {
                $finalRankedItems[] = ['item' => $itemsMap[$itemId], 'score' => $rankedResultsWithScores[$itemId]];
            }
        }
        usort($finalRankedItems, fn($a, $b) => $b['score'] <=> $a['score']);
        return $finalRankedItems;
    }

    private function extractKeywordsFromItemProfile(Item $idealProfileItem): array
    {
        $textToProcess = '';
        if (isset($idealProfileItem->description) && !empty($idealProfileItem->description)) {
            $textToProcess .= ' ' . strtolower($idealProfileItem->description);
        }
        if (isset($idealProfileItem->brand_name_for_keyword) && !empty($idealProfileItem->brand_name_for_keyword)) {
            $textToProcess .= ' ' . strtolower($idealProfileItem->brand_name_for_keyword);
        }
        if (isset($idealProfileItem->btu_capacity) && $idealProfileItem->btu_capacity) {
            if ($idealProfileItem->btu_capacity == 5000) $textToProcess .= ' 0.5pk setengahpk 5000btu';
            else if ($idealProfileItem->btu_capacity == 7000) $textToProcess .= ' 0.75pk tigaperempatpk 7000btu';
            else if ($idealProfileItem->btu_capacity == 9000) $textToProcess .= ' 1pk satupk 9000btu';
            else if ($idealProfileItem->btu_capacity == 12000) $textToProcess .= ' 1.5pk satusetengahpk 12000btu';
            else if ($idealProfileItem->btu_capacity == 18000) $textToProcess .= ' 2pk duapk 18000btu';
        }
        if (isset($idealProfileItem->is_inverter)) {
            $textToProcess .= $idealProfileItem->is_inverter ? ' inverter' : ' standard noninverter';
        }
        if (isset($idealProfileItem->warranty_compressor_years) && $idealProfileItem->warranty_compressor_years) {
            $textToProcess .= ' garansi ' . $idealProfileItem->warranty_compressor_years . 'tahun';
        }

        if (empty(trim($textToProcess))) return [];

        $textToProcess = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $textToProcess);
        $textToProcess = str_replace('-', ' ', $textToProcess);
        $textToProcess = preg_replace('/\s+/', ' ', trim($textToProcess));

        $tokens = array_filter(array_unique(explode(' ', $textToProcess)));

        $stopwords = ['dan', 'di', 'atau', 'dengan', 'untuk', 'yang', 'ini', 'itu', 'ac', 'pk', 'rp', 'harga', 'jual', 'watt', 'btu', 'tipe', 'jenis', 'series', 'fitur', 'mode', 'nya', 'semua', 'maks', 'min', 'tahun', 'thn', 'kompresor', 'pendingin', 'ruangan', 'unit', 'outdoor', 'indoor'];
        $keywords = array_udiff($tokens, $stopwords, 'strcasecmp');

        return array_values(array_filter($keywords, fn($kw) => strlen(trim($kw)) > 2 && !is_numeric(trim($kw))));
    }

    private function preloadItemKeywords(Collection $itemIds): void
    {
        $idsToFetch = $itemIds->diff(array_keys($this->allKeywordsCache));
        if ($idsToFetch->isNotEmpty()) {
            $newKeywords = ItemKeyword::whereIn('item_id', $idsToFetch->all())
                ->get(['item_id', 'keyword_name'])
                ->groupBy('item_id')
                ->map(fn($keywordsForItem) => $keywordsForItem->pluck('keyword_name')->unique()->toArray())
                ->all();
            $this->allKeywordsCache = array_merge($this->allKeywordsCache, $newKeywords);
        }
    }

    private function getItemKeywords(int $itemId): array
    {
        if ($itemId === 0) return [];

        if (!isset($this->allKeywordsCache[$itemId])) {
            $keywords = ItemKeyword::where('item_id', $itemId)->pluck('keyword_name')->unique()->toArray();
            $this->allKeywordsCache[$itemId] = $keywords;
            return $keywords;
        }
        return $this->allKeywordsCache[$itemId];
    }

    private function calculateContentBasedSimilarity(Item $item1, Item $item2): float
    {
        $keywords1 = ($item1->id === 0) ? $this->extractKeywordsFromItemProfile($item1) : $this->getItemKeywords($item1->id);
        $keywords2 = $this->getItemKeywords($item2->id);

        if (empty($keywords1) || empty($keywords2)) return 0.0;

        $intersection = count(array_intersect($keywords1, $keywords2));
        $denominator = count($keywords1) + count($keywords2);

        return ($denominator == 0) ? 0.0 : (2.0 * $intersection) / $denominator;
    }

    private function calculateCaseBasedSimilarity(Item $item1, Item $item2): float
    {
        $totalSimilarity = 0.0;
        $totalActiveWeight = 0.0;
        $configs = $this->configurations;
        $isIdealProfile = ($item1->id === 0);

        $attributesDefinitions = [
            'price'                     => ['weight_key' => 'cb_attr_price_weight', 'max_key' => 'cb_attr_price_max_value', 'type' => 'numeric_lte'],
            'btu_capacity'              => ['weight_key' => 'cb_attr_btu_capacity_weight', 'max_key' => 'cb_attr_btu_capacity_max_value', 'type' => 'numeric'],
            'power_consumption_watt'    => ['weight_key' => 'cb_attr_power_consumption_watt_weight', 'max_key' => 'cb_attr_power_consumption_watt_max_value', 'type' => 'numeric_lte'],
            'is_inverter'               => ['weight_key' => 'cb_attr_is_inverter_weight', 'max_key' => 'cb_is_inverter_max_value', 'type' => 'boolean_exact'],
            'room_size_max_sqm'         => ['weight_key' => 'cb_attr_room_size_weight', 'max_key' => 'cb_attr_room_size_max_value', 'type' => 'numeric_lte'],
            'warranty_compressor_years' => ['weight_key' => 'cb_attr_warranty_weight', 'max_key' => 'cb_attr_warranty_max_value', 'type' => 'numeric_gte'],
        ];

        foreach ($attributesDefinitions as $attributeColumn => $config) {
            $weight = (float)($configs[$config['weight_key']] ?? 0.0);
            if ($weight <= 0.0) continue;

            $value1_ideal = $this->getItemAttributeValue($item1, $attributeColumn);
            $value2_candidate = $this->getItemAttributeValue($item2, $attributeColumn);

            if ($isIdealProfile && $value1_ideal === null) {
                continue;
            }

            if (($value1_ideal === null || $value2_candidate === null) && strpos($config['type'], 'boolean') !== 0) {
                continue;
            }

            $maxValueForNormalization = (float)($configs[$config['max_key']] ?? 1.0);
            if ($maxValueForNormalization <= 0) $maxValueForNormalization = 1.0;

            $attributeSimilarity = 0.0;

            $v1 = ($config['type'] === 'boolean_exact') ? (bool)($value1_ideal ?? false) : (float)($value1_ideal ?? 0);
            $v2 = ($config['type'] === 'boolean_exact') ? (bool)($value2_candidate ?? false) : (float)($value2_candidate ?? 0);

            switch ($config['type']) {
                case 'numeric':
                    $diff = abs($v1 - $v2);
                    $normalizedDiff = ($maxValueForNormalization > 0) ? ($diff / $maxValueForNormalization) : ($diff > 0 ? 1 : 0);
                    $attributeSimilarity = max(0.0, 1.0 - $normalizedDiff);
                    break;
                case 'numeric_lte':
                    if ($v2 <= $v1) {
                        $attributeSimilarity = 1.0;
                    } else {
                        $diff_over = $v2 - $v1;
                        $penalty_normalized = ($v1 > 0) ? ($diff_over / $v1) : 1;
                        $attributeSimilarity = max(0.0, 1.0 - $penalty_normalized);
                    }
                    break;
                case 'numeric_gte':
                    if ($v2 >= $v1) {
                        $attributeSimilarity = 1.0;
                    } else {
                        $diff_under = $v1 - $v2;
                        $penalty_normalized = ($v1 > 0) ? ($diff_under / $v1) : 1;
                        $attributeSimilarity = max(0.0, 1.0 - $penalty_normalized);
                    }
                    break;
                case 'boolean_exact':
                    $attributeSimilarity = ($v1 === $v2) ? 1.0 : 0.0;
                    break;
            }
            $totalSimilarity += $weight * $attributeSimilarity;
            $totalActiveWeight += $weight;
        }

        return ($totalActiveWeight > 0) ? ($totalSimilarity / $totalActiveWeight) : 0.0;
    }

    private function getItemAttributeValue(Item $item, string $attributeColumn)
    {
        if ($item->id === 0 && !isset($item->{$attributeColumn})) {
            return null;
        }
        return $item->{$attributeColumn} ?? null;
    }

    private function calculateHybridScore(float $contentScore, float $caseScore, ?float $customContentWeight = null, ?float $customCaseWeight = null): float
    {
        $configs = $this->configurations;

        $contentWeight = $customContentWeight ?? (float)($configs['content_based_overall_weight'] ?? 0.3);
        $caseWeight = $customCaseWeight ?? (float)($configs['case_based_overall_weight'] ?? 0.7);

        if ($contentWeight < 0) $contentWeight = 0;
        if ($caseWeight < 0) $caseWeight = 0;

        $totalMainWeight = $contentWeight + $caseWeight;
        if ($totalMainWeight <= 0) return 0.0;

        $normalizedContentWeight = $contentWeight / $totalMainWeight;
        $normalizedCaseWeight = $caseWeight / $totalMainWeight;

        return ($contentScore * $normalizedContentWeight) + ($caseScore * $normalizedCaseWeight);
    }
}
