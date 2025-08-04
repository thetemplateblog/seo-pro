<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class TwitterCardMetadata extends Rule
{
    use Concerns\WarnsWhenPagesDontPass;

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.twitter_card_metadata.description');
    }

    public function pageDescription()
    {
        return __('seo-pro::messages.rules.twitter_card_metadata.description');
    }

    public function siteWarningComment()
    {
        return __('seo-pro::messages.rules.twitter_card_metadata.warning', ['count' => $this->failures]);
    }

    public function pageWarningComment()
    {
        return __('seo-pro::messages.rules.twitter_card_metadata.warning');
    }

    public function siteFailingComment()
    {
        return __('seo-pro::messages.rules.twitter_card_metadata.fail', ['count' => $this->failures]);
    }

    public function pageFailingComment()
    {
        return __('seo-pro::messages.rules.twitter_card_metadata.fail');
    }

    public function processPage()
    {
        // Page processing for Twitter Card metadata
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
        $twitterCard = $this->page->get('twitter_card');
        $twitterTitle = $this->page->get('twitter_title');
        $twitterDescription = $this->page->get('twitter_description');
        
        // Twitter cards work well with just twitter:card set
        // They will fall back to OG data for title/description if not specified
        // We skip twitter:image validation completely
        
        // Must have twitter:card set
        if (empty($twitterCard)) {
            return 'fail';
        }
        
        // If twitter:card is set but no Twitter-specific title/description,
        // check if we have OG fallbacks
        if (empty($twitterTitle) && empty($twitterDescription)) {
            $ogTitle = $this->page->get('og_title');
            $ogDescription = $this->page->get('og_description');
            
            // Warn if there's no Twitter metadata AND no OG fallbacks
            if (empty($ogTitle) && empty($ogDescription)) {
                return 'warning';
            }
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
        return __('seo-pro::messages.rules.twitter_card_metadata.pill');
    }
}