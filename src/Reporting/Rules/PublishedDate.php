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
        // Skip taxonomy/term pages and other non-entry pages
        $id = $this->page->get('id');
        if (is_string($id) && str_contains($id, '::')) {
            return 'pass'; // Taxonomy pages don't need published dates
        }
        
        // Check if this is a dated entry (has a date in the data)
        $publishedDate = $this->page->get('published_date');
        
        // If there's no published date, check if this is a regular page
        // Pages that aren't blog entries/articles don't need published dates
        if (empty($publishedDate)) {
            // If the page doesn't have typical blog entry indicators, pass it
            $hasDate = !empty($this->page->get('date'));
            $hasAuthor = !empty($this->page->get('author'));
            
            // If it has neither date nor author fields, it's likely a regular page
            if (!$hasDate && !$hasAuthor) {
                return 'pass';
            }
            
            // If it has updated_date but no published_date, it's likely a page
            $updatedDate = $this->page->get('updated_date');
            if (!empty($updatedDate)) {
                return 'pass';
            }
            
            return 'fail';
        }
        
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