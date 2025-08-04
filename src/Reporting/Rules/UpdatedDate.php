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

    public function siteFailingComment()
    {
        return __('seo-pro::messages.rules.updated_date.fail', ['count' => $this->failures]);
    }

    public function pageFailingComment()
    {
        return __('seo-pro::messages.rules.updated_date.fail');
    }

    public function processPage()
    {
        // The trait will handle the page processing based on pageStatus()
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
        // Skip taxonomy/term pages as they may not have traditional updated dates
        $id = $this->page->get('id');
        if (is_string($id) && str_contains($id, '::')) {
            return 'pass'; // Taxonomy pages get updated when content is added/removed
        }
        
        // Check if this page has an updated date
        $updatedDate = $this->page->get('updated_date');
        
        // If it has an updated date, pass
        if (!empty($updatedDate)) {
            return 'pass';
        }
        
        // For pages without updated dates, warn
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