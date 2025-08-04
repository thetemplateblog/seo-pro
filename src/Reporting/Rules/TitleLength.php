<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class TitleLength extends Rule
{
    use Concerns\WarnsWhenPagesDontPass;

    protected $minLength = 30;
    protected $maxLength = 60;

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.title_length.description');
    }

    public function pageDescription()
    {
        return __('seo-pro::messages.rules.title_length.description');
    }

    public function siteWarningComment()
    {
        return __('seo-pro::messages.rules.title_length.warning', ['count' => $this->failures]);
    }

    public function pageWarningComment()
    {
        return __('seo-pro::messages.rules.title_length.warning');
    }

    public function processPage()
    {
        // Page processing for title length
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
        $title = $this->page->get('title');
        
        if (empty($title)) {
            return 'warning';
        }
        
        $length = mb_strlen($title);
        
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
        return __('seo-pro::messages.rules.title_length.pill');
    }
}