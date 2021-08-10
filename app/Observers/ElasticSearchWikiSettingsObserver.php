<?php

namespace App\Observers;

use App\WikiSetting;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Jobs\ElasticSearchIndexInit;

/**
 * Class for acting on changes to elasticsearch settings for a particular wiki
 * 
 * These do not trigger on bulk updates
 */
class ElasticSearchWikiSettingsObserver
{
    use DispatchesJobs;

    private function updateOrCreate(WikiSetting $wikiSetting): void {

        $lexemeJustEnabled = $wikiSetting->name === WikiSetting::wwExtEnableWikibaseLexeme && $wikiSetting->value === '1';
        $elasticSearchJustEnabled =  $wikiSetting->name === WikiSetting::wwExtEnableElasticSearch && $wikiSetting->value === '1';

        // if either of these get enabled - check the dependent setting
        if( $lexemeJustEnabled || $elasticSearchJustEnabled ) {
            
            $wiki = $wikiSetting->wiki()->with('settings')->first();

            // not deleted or non-existing
            if( $wiki !== null ) {
                
                if( $elasticSearchJustEnabled ) {
                    $this->dispatch( new ElasticSearchIndexInit( $wiki->domain ) );
                } else {

                    // Lexeme just got enabled check for elastic search
                    $elasticSearchIsEnabled = $wiki->settings()->where([
                        'name' => WikiSetting::wwExtEnableElasticSearch,
                        'value' => '1'
                    ])->first();

                    if ( $elasticSearchIsEnabled ) {
                        $this->dispatch( new ElasticSearchIndexInit( $wiki->domain ) );
                    }
                }

            }
        }
    }

    /**
     * Handle the wiki setting "created" event.
     *
     * @param  \App\WikiSetting  $wikiSetting
     * @return void
     */
    public function created(WikiSetting $wikiSetting)
    { 
        $this->updateOrCreate($wikiSetting);
    }

    /**
     * Handle the wiki setting "updated" event.
     *
     * @param  \App\WikiSetting  $wikiSetting
     * @return void
     */
    public function updated(WikiSetting $wikiSetting)
    {
        $this->updateOrCreate($wikiSetting);
    }

    /**
     * Handle the wiki setting "deleted" event.
     *
     * @param  \App\WikiSetting  $wikiSetting
     * @return void
     */
    public function deleted(WikiSetting $wikiSetting)
    {
        // todo delete some index
    }

    /**
     * Handle the wiki setting "restored" event.
     *
     * @param  \App\WikiSetting  $wikiSetting
     * @return void
     */
    public function restored(WikiSetting $wikiSetting)
    {
        // todo restore some index?
    }

    /**
     * Handle the wiki setting "force deleted" event.
     *
     * @param  \App\WikiSetting  $wikiSetting
     * @return void
     */
    public function forceDeleted(WikiSetting $wikiSetting)
    {
        // todo do something?
    }
}
