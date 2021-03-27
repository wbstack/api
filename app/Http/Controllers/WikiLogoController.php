<?php

namespace App\Http\Controllers;

use App\WikiManager;
use App\WikiSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Illuminate\Contracts\Filesystem\Cloud;

class WikiLogoController extends Controller
{

    /**
     * It would be beneficial to have a bit of atomicness here?
     * Right now WgLogo is always the same path when set, so if we start writing new files but die we still end up updating the site.
     * Fine for now but...
     */
    public function update(Request $request)
    {
        $request->validate([
            'wiki' => 'required|numeric',
            'logo' => 'required|mimes:png',
        ]);

        $user = $request->user();

        $wikiId = $request->input('wiki');
        $userId = $user->id;

        // Check that the requesting user manages the wiki
        // TODO turn this into a generic guard for all of these types of routes...
        if( WikiManager::where( 'user_id', $userId )->where( 'wiki_id', $wikiId )->count() !== 1 ) {
            // The deletion was requested by a user that does not manage the wiki
            return response()->json('Unauthorized', 401);
        }

        // Get the cloudy disk we use to store logos
        $disk = Storage::disk('gcs-public-static');
        if ( !$disk instanceof Cloud ) {
            return response()->json('Invalid storage (not cloud)', 500);
        }

        // Get a directory for storing all things relating to this site
        // TODO should be in the site model? maybe?
        $siteDir = md5( $wikiId . md5( $wikiId ) );
        $logosDir = 'sites/' . $siteDir . '/logos';

        // Delete the old raw file if it was already there
        $rawFilePath = $logosDir . '/raw.png';
        if( $disk->exists( $rawFilePath ) ) {
            $disk->delete( $rawFilePath );
        }

        // Store the raw file uploaded by the user
        $request->file('logo' )->storeAs(
            $logosDir,
            'raw.png',
            'gcs-public-static'
        );

        // Store a conversion for the actual site logo
        $reducedPath = $logosDir . '/135.png';
        if( $disk->exists( $reducedPath ) ) {
            $disk->delete( $reducedPath );
        }
        $disk->writeStream(
            $reducedPath,
            Image::make($request->file('logo')->getRealPath())->resize(135, 135)->stream()->detach()
        );

        // And a favicon
        $faviconPath = $logosDir . '/64.ico';
        if( $disk->exists( $faviconPath ) ) {
            $disk->delete( $faviconPath );
        }
        $disk->writeStream(
            $faviconPath,
            Image::make($request->file('logo')->getRealPath())->encode('ico')->resize(64, 64)->stream()->detach()
        );

        // Get the urls
        $logoUrl = $disk->url( $reducedPath );
        $faviconUrl = $disk->url( $faviconPath );
        // Append the time to the url so that client caches will be invalidated
        $logoUrl .= '?u=' . time();
        $faviconUrl .= '?u=' . time();

        // Docs: https://www.mediawiki.org/wiki/Manual:$wgLogo
        WikiSetting::updateOrCreate(
            [
                'wiki_id' => $wikiId,
                'name' => 'wgLogo',
            ],
            [
                'value' => $logoUrl,
            ]
        );
        // Docs: https://www.mediawiki.org/wiki/Manual:$wgFavicon
        WikiSetting::updateOrCreate(
            [
                'wiki_id' => $wikiId,
                'name' => 'wgFavicon',
            ],
            [
                'value' => $faviconUrl,
            ]
        );

        $res['success'] = true;
        $res['url'] = $logoUrl;
        return response($res);
    }
}
