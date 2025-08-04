<?php

namespace Tests;

use Statamic\Facades\Entry;
use Statamic\Facades\Term;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\Reporting\Page;
use Statamic\SeoPro\Reporting\Report;
use Statamic\SeoPro\Reporting\Rules\AuthorMetadata;
use Statamic\SeoPro\Reporting\Rules\DescriptionLength;
use Statamic\SeoPro\Reporting\Rules\OpenGraphMetadata;
use Statamic\SeoPro\Reporting\Rules\PublishedDate;
use Statamic\SeoPro\Reporting\Rules\TitleLength;
use Statamic\SeoPro\Reporting\Rules\UpdatedDate;
use Statamic\SeoPro\Reporting\Rules\TwitterCardMetadata;
use Statamic\SeoPro\SiteDefaults;

class RulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear all entries AFTER parent setup copies fixtures
        Entry::all()->each->delete();
        Term::all()->each->delete();
        
        // Clear Stache to ensure entries are properly removed
        \Statamic\Facades\Stache::clear();
        
        // Also clear any report files that might have been created
        if ($this->files->exists($path = storage_path('statamic/seopro/reports'))) {
            $this->files->deleteDirectory($path);
        }
    }
    
    protected function createPageWithData($data, $entryData = [])
    {
        $report = Report::create();
        
        // Create an entry if we need specific entry data
        if (!empty($entryData)) {
            $entry = Entry::make()
                ->collection('articles') 
                ->slug('test-entry')
                ->data(array_merge(['title' => 'Test Entry'], $entryData));
                
            if (isset($entryData['date'])) {
                $entry->date($entryData['date']);
            }
            
            $entry->save();
            
            $cascade = (new Cascade)
                ->with(SiteDefaults::load()->all())
                ->withCurrent($entry)
                ->with($data);
        } else {
            // Just use cascade data without an entry
            $defaults = [
                'title' => 'Test Page',
                'description' => null,
                'canonical_url' => 'http://test.com/page',
            ];
            $mergedData = array_merge($defaults, $data);
            $cascade = (new Cascade)->with(SiteDefaults::load()->all())->with($mergedData);
        }
        
        $cascadeData = $cascade->get();
        return new Page('test-id', $cascadeData, $report);
    }

    /** @test */
    public function published_date_rule_passes_when_date_exists()
    {
        $page = $this->createPageWithData([], ['date' => '2023-05-15']);
        
        $rule = new PublishedDate();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function published_date_rule_passes_when_collection_doesnt_require_dates()
    {
        // Create an entry in a collection that doesn't require published dates
        $page = $this->createPageWithData([
            'published_date' => null  // Explicitly set to null
        ], []);
        
        $rule = new PublishedDate();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        // Should pass because the collection doesn't have require_published_date enabled
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function author_metadata_rule_passes_when_author_exists()
    {
        $page = $this->createPageWithData([], ['author' => 'John Doe']);
        
        $rule = new AuthorMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function author_metadata_rule_warns_when_author_is_missing()
    {
        $page = $this->createPageWithData([]);
        
        $rule = new AuthorMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('warning', $rule->pageStatus());
    }

    /** @test */
    public function open_graph_metadata_rule_passes_with_complete_data()
    {
        $page = $this->createPageWithData([
            'og_type' => 'article',
            'og_image' => 'image.jpg',
            'og_description' => 'Description',
        ]);
        
        $rule = new OpenGraphMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function open_graph_metadata_rule_passes_with_fallback_data()
    {
        $page = $this->createPageWithData([
            'og_description' => 'Description',
        ]);
        
        $rule = new OpenGraphMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function open_graph_metadata_rule_passes_with_image()
    {
        $page = $this->createPageWithData([
            'og_type' => 'article',
            'og_image' => 'image.jpg',
        ]);
        
        $rule = new OpenGraphMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function twitter_card_metadata_rule_passes_with_fallback_data()
    {
        $page = $this->createPageWithData([]);
        
        $rule = new TwitterCardMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function twitter_card_metadata_rule_passes_with_complete_twitter_data()
    {
        $page = $this->createPageWithData([
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'Title',
            'twitter_description' => 'Description',
            'twitter_image' => 'image.jpg',
        ]);
        
        $rule = new TwitterCardMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function twitter_card_metadata_rule_passes_with_partial_twitter_data()
    {
        $page = $this->createPageWithData([
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'Title',
        ]);
        
        $rule = new TwitterCardMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function published_date_rule_processes_correctly()
    {
        // Create entries that won't have require_published_date enabled
        collect(range(1, 3))->each(function ($i) {
            $entry = Entry::make()
                ->collection('pages')
                ->slug('test-page-'.$i)  
                ->set('title', 'Test Page '.$i);
                
            // Only the first 2 entries get published_date in their data
            if ($i <= 2) {
                $entry->set('published_date', '2023-05-15');
            }
            
            $entry->save();
        });
        
        // Clear any cached content to ensure fresh data
        \Statamic\Facades\Stache::clear();
        
        $report = Report::create()->save();
        $report->clearCaches();
        $report->generate();
        
        $result = $this->getReportResult('PublishedDate');
        
        // All should pass since the collection doesn't have require_published_date enabled
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function author_metadata_rule_processes_correctly()
    {
        $this->generateEntries(3);
        
        Entry::all()
            ->take(2)
            ->each(fn ($entry) => $entry->data(['author' => 'Test Author'])->save());
        
        Report::create()->save()->generate();
        
        $result = $this->getReportResult('AuthorMetadata');
        $this->assertEquals(1, $result); // 1 entry missing author
    }

    /** @test */
    public function open_graph_metadata_rule_processes_correctly()
    {
        $this->generateEntries(3);
        
        // All entries get og_type from site defaults ('website')
        // So all should pass
        Report::create()->save()->generate();
        
        $result = $this->getReportResult('OpenGraphMetadata');
        $this->assertEquals(0, $result); // All pass
    }

    /** @test */
    public function twitter_card_metadata_rule_processes_correctly()
    {
        $this->generateEntries(3);
        
        // All entries get twitter_card from site defaults
        // And they have OG fallbacks, so all should pass
        Report::create()->save()->generate();
        
        $result = $this->getReportResult('TwitterCardMetadata');
        $this->assertEquals(0, $result); // All pass
    }

    protected function generateEntries($count)
    {
        collect(range(1, $count))->each(function ($i) {
            Entry::make()
                ->collection('articles')
                ->blueprint('article')
                ->slug('test-entry-'.$i)
                ->set('title', 'Test Entry '.$i)
                ->save();
        });

        return $this;
    }

    /** @test */
    public function published_date_rule_passes_when_entry_has_published_date()
    {
        $page = $this->createPageWithData(['published_date' => '2023-05-15'], []);
        
        $rule = new PublishedDate();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('pass', $result->status());
    }

    /** @test */
    public function published_date_rule_passes_when_no_entry()
    {
        $page = $this->createPageWithData([]);
        
        $rule = new PublishedDate();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('pass', $result->status());
    }

    /** @test */
    public function author_metadata_rule_passes_when_author_present()
    {
        // Author needs to be on the entry, not just in cascade data
        $page = $this->createPageWithData([], ['author' => 'John Doe']);
        
        $rule = new AuthorMetadata();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('pass', $result->status());
    }

    /** @test */
    public function author_metadata_rule_warns_when_author_missing()
    {
        $page = $this->createPageWithData([]);
        
        $rule = new AuthorMetadata();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('warning', $result->status());
        $this->assertStringContainsString('author information', $result->comment());
    }

    /** @test */
    public function open_graph_metadata_rule_passes_without_image_validation()
    {
        $page = $this->createPageWithData(['og_title' => 'OG Title']);
        
        $rule = new OpenGraphMetadata();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('pass', $result->status());
    }

    /** @test */
    public function twitter_card_metadata_rule_passes_without_image_validation()
    {
        $page = $this->createPageWithData([
            'twitter_card' => 'summary_large_image',
            'og_title' => 'OG Title',
            'og_description' => 'OG Description'
        ]);
        
        $rule = new TwitterCardMetadata();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('pass', $result->status());
    }

    /** @test */
    public function published_date_rule_passes_for_regular_pages()
    {
        $page = $this->createPageWithData([
            'url' => 'https://example.com/about'
        ]);
        
        $rule = new PublishedDate();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('pass', $result->status());
    }

    /** @test */
    public function updated_date_rule_passes_when_date_exists()
    {
        // Create a page with updated_date directly since Cascade's updatedDate() 
        // relies on Entry's lastModified() which is not easily testable
        $page = new Page('test-id', [
            'updated_date' => '2024-01-15T10:00:00+00:00',
            'canonical_url' => 'http://test.com/page',
        ], Report::create());
        
        $rule = new UpdatedDate();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('pass', $result->status());
    }

    /** @test */
    public function updated_date_rule_warns_when_date_is_missing()
    {
        $page = $this->createPageWithData([]);
        
        $rule = new UpdatedDate();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('warning', $result->status());
        $this->assertStringContainsString('when content was last modified', $result->comment());
    }


    /** @test */
    public function title_length_rule_passes_when_title_is_optimal_length()
    {
        $page = $this->createPageWithData([
            'title' => 'This is a perfectly sized title for SEO' // 39 characters
        ]);
        
        $rule = new TitleLength();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('pass', $result->status());
    }

    /** @test */
    public function title_length_rule_warns_when_title_is_too_short()
    {
        $page = $this->createPageWithData([
            'title' => 'Short Title' // 11 characters
        ]);
        
        $rule = new TitleLength();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('warning', $result->status());
        $this->assertStringContainsString('not optimal', $result->comment());
    }

    /** @test */
    public function title_length_rule_warns_when_title_is_too_long()
    {
        $page = $this->createPageWithData([
            'title' => 'This is an extremely long title that will definitely be truncated in search engine results pages' // 96 characters
        ]);
        
        $rule = new TitleLength();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('warning', $result->status());
    }

    /** @test */
    public function title_length_rule_warns_when_title_is_empty()
    {
        $page = $this->createPageWithData([
            'title' => ''
        ]);
        
        $rule = new TitleLength();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('warning', $result->status());
    }

    /** @test */
    public function description_length_rule_passes_when_description_is_optimal_length()
    {
        $page = $this->createPageWithData([
            'description' => 'This is a well-crafted meta description that provides a compelling summary of the page content while staying within the optimal character limit for search.' // 155 characters
        ]);
        
        $rule = new DescriptionLength();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('pass', $result->status());
    }

    /** @test */
    public function description_length_rule_warns_when_description_is_too_short()
    {
        $page = $this->createPageWithData([
            'description' => 'This is a short description that does not provide enough detail' // 63 characters
        ]);
        
        $rule = new DescriptionLength();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('warning', $result->status());
        $this->assertStringContainsString('not optimal', $result->comment());
    }

    /** @test */
    public function description_length_rule_warns_when_description_is_too_long()
    {
        $page = $this->createPageWithData([
            'description' => 'This is an overly long meta description that will definitely be truncated by search engines. It contains far too much information and should be shortened to ensure the full message is visible in search results without being cut off mid-sentence.' // 246 characters
        ]);
        
        $rule = new DescriptionLength();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('warning', $result->status());
    }

    /** @test */
    public function description_length_rule_warns_when_description_is_empty()
    {
        $page = $this->createPageWithData([
            'description' => ''
        ]);
        
        $rule = new DescriptionLength();
        $result = $rule->setPage($page)->process();
        
        $this->assertEquals('warning', $result->status());
    }

    protected function getReportResult($key)
    {
        return \Statamic\Facades\YAML::file(storage_path('statamic/seopro/reports/1/report.yaml'))->parse()['results'][$key];
    }
}