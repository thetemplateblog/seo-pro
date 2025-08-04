<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class UpdatedDate extends Rule
{
    use Concerns\WarnsWhenPagesDontPass;

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.updated_date.description');
    }

    public function pageDescription()
    {
        return __('seo-pro::messages.rules.updated_date.description');
    }

    public function siteWarningComment()
    {
        return __('seo-pro::messages.rules.updated_date.warning', ['count' => $this->failures]);
    }

    public function pageWarningComment()
    {
        return __('seo-pro::messages.rules.updated_date.warning');
    }

    public function processPage()
    {
        // Page processing for updated date metadata
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
        // Check if this page has an updated date
        $updatedDate = $this->page->get('updated_date');
        
        // If it has an updated date, pass
        if (!empty($updatedDate)) {
            return 'pass';
        }
        
        // Get the model to determine content type
        $model = $this->page->model();
        
        // Check if this is a taxonomy term (Terms don't need updated dates)
        if ($model instanceof \Statamic\Taxonomies\LocalizedTerm || 
            $model instanceof \Statamic\Taxonomies\Term) {
            return 'pass';
        }
        
        // For all other content (entries, pages, etc.), warn about missing updated date
        return 'warning';
    }

    public function maxPoints()
    {
        return $this->report->pages()->count();
    }

    public function demerits()
    {
        return $this->failures * 0.5; // Less severe than missing published date
    }

    public function actionablePill()
    {
        return __('seo-pro::messages.rules.updated_date.pill');
    }
}