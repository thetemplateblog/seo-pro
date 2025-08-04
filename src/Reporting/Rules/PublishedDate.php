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
        
        // Check if this page has a published date
        $publishedDate = $this->page->get('published_date');
        
        // If it has a published date, pass
        if (!empty($publishedDate)) {
            return 'pass';
        }
        
        // Check if this looks like a dated entry based on URL pattern
        // Articles typically have date-based URLs or specific content patterns
        $url = $this->page->get('url') ?? '';
        $canonical = $this->page->get('canonical_url') ?? '';
        
        // Check for common blog/article URL patterns that should have dates
        $blogPatterns = [
            '/writing/',
            '/blog/',
            '/posts/',
            '/articles/',
            '/news/',
        ];
        
        $shouldHaveDate = false;
        foreach ($blogPatterns as $pattern) {
            if (str_contains($url, $pattern) || str_contains($canonical, $pattern)) {
                $shouldHaveDate = true;
                break;
            }
        }
        
        // Also check for date patterns in URL
        if (preg_match('/\/\d{4}\/\d{2}\//', $url) || 
            preg_match('/\/\d{4}\/\d{2}\//', $canonical)) {
            $shouldHaveDate = true;
        }
        
        // Check if this has typical blog metadata that suggests it should have a date
        // But only if it looks like a blog based on URL patterns
        if ($shouldHaveDate) {
            return 'fail';
        }
        
        // Check if this page has significant content metadata suggesting it's an article
        $hasAuthor = !empty($this->page->get('author'));
        $hasCategories = !empty($this->page->get('categories'));
        $title = $this->page->get('title') ?? '';
        
        // If it has an author and looks like article content, it should have a date
        if ($hasAuthor && ($hasCategories || str_contains(strtolower($title), 'article'))) {
            return 'fail';
        }
        
        // For all other pages (home, about, contact, etc.), pass
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