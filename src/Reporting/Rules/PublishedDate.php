<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class PublishedDate extends Rule
{
    use Concerns\FailsWhenPagesDontPass;

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.published_date.description');
    }

    public function pageDescription()
    {
        return __('seo-pro::messages.rules.published_date.description');
    }

    public function siteFailingComment()
    {
        return __('seo-pro::messages.rules.published_date.fail', ['count' => $this->failures]);
    }

    public function pageFailingComment()
    {
        return __('seo-pro::messages.rules.published_date.fail');
    }

    public function processPage()
    {
        // Page processing for Published Date
    }

    public function savePage()
    {
        return $this->pageStatus();
    }

    public function loadPage($data)
    {
        // Not used for this rule
    }

    public function pageStatus()
    {
        // Check if this page has a published date
        $publishedDate = $this->page->get('published_date');
        
        // If it has a published date, pass
        if (!empty($publishedDate)) {
            return 'pass';
        }
        
        // Get the model to determine if this is a collection entry or taxonomy term
        $model = $this->page->model();
        
        // If we can't get the model, be lenient and pass
        if (!$model) {
            return 'pass';
        }
        
        // Check if this is a taxonomy term (Terms don't need published dates)
        if ($model instanceof \Statamic\Taxonomies\LocalizedTerm || 
            $model instanceof \Statamic\Taxonomies\Term) {
            return 'pass';
        }
        
        // Check if this is an entry
        if ($model instanceof \Statamic\Entries\Entry) {
            $collection = $model->collection();
            
            // If it's a dated collection, it should have a published date
            if ($collection && $collection->dated()) {
                return 'fail';
            }
            
            // Check if the collection is configured to require dates
            // Collections like 'articles', 'blog', 'news' typically need dates
            $handle = $collection ? $collection->handle() : '';
            $datedCollections = ['articles', 'blog', 'posts', 'news', 'updates'];
            
            if (in_array($handle, $datedCollections)) {
                return 'fail';
            }
        }
        
        // For all other content types (pages, etc.), pass
        return 'pass';
    }

    public function maxPoints()
    {
        return $this->report->pages()->count();
    }

    public function demerits()
    {
        return $this->failures;
    }

    public function actionablePill()
    {
        return __('seo-pro::messages.rules.published_date.pill');
    }
}