<?php

namespace App\Jobs;

use App\Wiki;
use App\WikiSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateOAuth2KeysJob extends Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $allWikis = Wiki::all();

        foreach ($allWikis as $wiki) {
            try {
                $hasPrivateKey = WikiSetting::where('wiki_id', $wiki->id)
                ->where('name', 'wgOAuth2PrivateKey')
                ->exists();

                if (!$hasPrivateKey) {
                    $keyPair = openssl_pkey_new([
                        'private_key_bits' => 2048,
                        'private_key_type' => OPENSSL_KEYTYPE_RSA,
                    ]);
                    // Extract private key
                    openssl_pkey_export($keyPair, $wgOAuth2PrivateKey);
                    // Extract pub key
                    $keyDetails = openssl_pkey_get_details($keyPair);
                    $wgOAuth2PublicKey = $keyDetails['key'];

                    WikiSetting::create([
                        'wiki_id' => $wiki->id,
                        'name' => WikiSetting::wgOAuth2PrivateKey,
                        'value' => $wgOAuth2PrivateKey,
                    ]);

                    WikiSetting::create([
                        'wiki_id' => $wiki->id,
                        'name' => WikiSetting::wgOAuth2PublicKey,
                        'value' => $wgOAuth2PublicKey,
                    ]);
                }
            } catch (\Exception $ex) {
                $this->job->markAsFailed();
                Log::error(
                    'Failure generating keys for '.$wiki->getAttribute('domain').' for sitestats: '.$ex->getMessage()
                );
            }
        }

    }
}
