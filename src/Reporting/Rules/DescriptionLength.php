<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class DescriptionLength extends Rule
{
    use Concerns\WarnsWhenPagesDontPass;

    protected $minLength = 120;
    protected $maxLength = 160;

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.description_length.description');
    }

    public function pageDescription()
    {
        return __('seo-pro::messages.rules.description_length.description');
    }

    public function siteWarningComment()
    {
        return __('seo-pro::messages.rules.description_length.warning', ['count' => $this->failures]);
    }

    public function pageWarningComment()
    {
        return __('seo-pro::messages.rules.description_length.warning');
    }

    public function processPage()
    {
        // Page processing for description length
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
        $description = $this->page->get('description');
        
        if (empty($description)) {
            return 'warning';
        }
        
        $length = mb_strlen($description);
        
        if ($length < $this->minLength || $length > $this->maxLength) {
            return 'warning';
        }
        
        return 'pass';
    }

    public function maxPoints()
    {
        return $this->report->pages()->count();
    }

    public function demerits()
    {
        return $this->failures * 0.5;
    }

    public function actionablePill()
    {
        return __('seo-pro::messages.rules.description_length.pill');
    }
}