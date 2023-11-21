<?php

namespace App\Console\Commands;

use App\Models\Scrap;
use Carbon\Carbon;
use Goutte\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class zorgkaartnederland extends Command
{
    protected $client;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:zorgkaartnederland';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape data from zorgkaartnederland and store it in the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->client = new Client();

        $categoriesUrl = 'https://www.zorgkaartnederland.nl/overzicht/organisatietypes';
        $crawler = $this->client->request('GET', $categoriesUrl);
        $categoryLinks = $crawler->filter('.col-md-6 a')->links();

        $processedCategories = $this->getProcessedCategories();

        // Loop through each category link
        foreach ($categoryLinks as $link) {

            $categoryUrl = $link->getUri();
            $categoryName = substr($categoryUrl, strrpos($categoryUrl, '/') + 1);

            if (in_array($categoryName, $processedCategories)) {
                $this->info("Skipping already processed category: $categoryName");
                continue;
            }

            $this->info("Processing category: $categoryUrl");
            $categoryCrawler = $this->client->request('GET', $categoryUrl);

            if ($categoryCrawler->filter('.pagination')->count() > 0) {
                $this->processPaginatedCategory($categoryCrawler, $categoryUrl);
            } else {
                $categoryName = substr($categoryUrl, strrpos($categoryUrl, '/') + 1);

                $this->processCategoryPage($categoryCrawler, $categoryUrl, $categoryName);
            }
        }

        $this->info('Scraping completed.');
    }


    protected function processPaginatedCategory($crawler, $categoryUrl)
    {
        $lastPageLink = $crawler->filter('.pagination .page-item:last-child .page-link');
        $totalPages = $lastPageLink->count() > 0 ? (int)$lastPageLink->attr('title') : 1;

        $categoryName = substr($categoryUrl, strrpos($categoryUrl, '/') + 1);

        for ($i = 1; $i <= $totalPages; $i++) {
            $pageUrl = $categoryUrl . '/pagina' . $i;
            $pageCrawler = $this->client->request('GET', $pageUrl);

            $this->processCategoryPage($pageCrawler, $pageUrl, $categoryName);
        }
    }


    protected function processCategoryPage($crawler, $categoryUrl, $categoryName)
    {
        $crawler->filter('.filter-result')->each(function ($node) use ($categoryName, $categoryUrl) {
            if ($node->count() === 0) {
                $this->info("No data found in the current node list.");
                return;
            }

            $name = $node->filter('.filter-result__body__left .filter-result__name')->text();
            $relativeDetailLink = $node->filter('.filter-result__body__left a.filter-result__name')->attr('href');
            $detailUrl = 'https://www.zorgkaartnederland.nl' . $relativeDetailLink;
            $detailPageCrawler = $this->client->request('GET', $detailUrl);
            if ($detailPageCrawler->count() === 0) {
                $this->info("No data found in the current node list for the detail page.");
                return;
            }

            // Check if "Bezoek website" link exists
            $urlElement = $detailPageCrawler->filter('.company-buttons a:contains("Bezoek website")');
            if ($urlElement->count() > 0) {
                $url = $urlElement->attr('href');
                if ($url === null || $url === '') {
                    $this->info("Failed to extract URL for: $name");
                    return;
                }
            } else {
                // Handle the case when "Bezoek website" link is not present
                $this->info("No 'Bezoek website' link found for: $name");
                return;
            }

            $page = ''; // Initialize the page variable
            if (strpos($categoryUrl, '/pagina') !== false) {
                $page = substr($categoryUrl, strrpos($categoryUrl, '/pagina') + 7);
            }

            // Update the source field to include the page number if it exists
            $source = 'https://www.zorgkaartnederland.nl/'.$categoryName;
            if ($page !== '') {
                $source .= '/pagina'.$page;
            }

            $existingRecord = DB::table('scraps')
                ->where('source', $source)
                ->where('category', $categoryName)
                ->where('name', $name)
                ->first();

            $now = Carbon::now();

            if (!$existingRecord) {
                DB::table('scraps')->insert([
                    'source' => $source,
                    'category' => $categoryName,
                    'name' => $name,
                    'url' => $url,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->info("Saved: $name - $url");
        });
    }

    protected function getLastProcessedCategory()
    {
        return 'https://www.zorgkaartnederland.nl/'.Scrap::latest()->first()?->category;
    }

    protected function getProcessedCategories()
    {
        // Retrieve processed categories from the database excluding the last processed category
        return DB::table('scraps')
            ->where('category', '<>', $this->getLastProcessedCategory())
            ->distinct()
            ->pluck('category')
            ->toArray();
    }
}
