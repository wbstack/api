<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ReviewSubmissionActionType;
use App\Http\Controllers\Controller;
use App\Services\MediaWikiHostResolver;
use App\Services\UnknownDBVersionException;
use App\Services\UnknownWikiDomainException;
use App\Wiki;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlatformNginxWikiInfoController extends Controller {
    public function getInfoForDomain(Request $request): Response {
        $domain = $request->query('domain');
        // TODO: inject the MediaWikiHostResolver so that we can pass in a mock when testing
        $mediawikiHostResolver = new MediaWikiHostResolver();
        try {
            $hosts = $mediawikiHostResolver->getHostsForDomain($domain);
        } catch (UnknownWikiDomainException $e) {
            return response('Domain not found', 404);
        } catch (UnknownDBVersionException $e) {
            return response('Unknown database version', 500);
        }

        if ($this->wikiInaccessible($domain)) {
            return response('Wiki suspended', 403);
        }

        return response('OK', 200, [
            'x-mw-alpha-host' => $hosts['alpha'],
            'x-mw-api-host' => $hosts['api'],
            'x-mw-backend-host' => $hosts['backend'],
            'x-mw-web-host' => $hosts['web'],
        ]);
    }

    // - wiki has no suspensions -> return accessible
    // - wiki's latest suspension is not active -> return accessible
    // - wiki's latest suspension is active and wiki has pending review submission with created_at date
    //   before the active_from date of the latest suspension -> return accessible
    // - wiki's latest suspension is active and wiki has pending review submission with created_at date
    //   after the active_from date of the latest suspension and the review has been picked up -> return accessible
    // - all other cases -> return not accessible
    private function wikiInaccessible(string $domain): bool {
        // TODO: Possible performance improvement is to look at the number of DB queries
        // eager loading using the with() method doesn't reduce the number of queries
        // TODO: We could also move this up in the controller so it doesn't happen in the MediaWikiHostResolver as well
        $wiki = Wiki::whereDomain($domain)->first();

        // - wiki has no suspensions -> return accessible
        // - wiki's latest suspension is not active -> return accessible
        if (!$wiki->latestSuspension?->is_active) {
            return false;
        }

        // - wiki's latest suspension is active and wiki has pending review submission with created_at date
        //   before the active_from date of the latest suspension -> return accessible
        // $wiki->latestSuspension is not null as already checked the $wiki->latestSuspension?->is_active state
        // non-pending reviews like accepted, rejected or cancelled are ignored here
        if ($wiki->pendingReviewSubmission?->created_at->isBefore($wiki->latestSuspension->active_from)) {
            return false;
        }

        // - wiki's latest suspension is active and wiki has pending review submission with created_at date after
        //   the active_from date of the latest suspension and the review has been picked up -> return accessible
        if ($wiki->pendingReviewSubmission?->latestAction->type === ReviewSubmissionActionType::REVIEW_STARTED) {
            return false;
        }

        // - all other cases -> return not accessible
        return true;
    }
}
