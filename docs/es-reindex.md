# Elastic Search - Re-Indexing a wiki

There are several situations in which we might have to re-index a wikis content into ES.

One way to do this is to use the API Job [ForceSearchIndex](/app/Jobs/CirrusSearch/ForceSearchIndex.php).

You can execute it like this:
```
kubectl exec -ti deployments/api-app-backend -- php artisan job:dispatchNow CirrusSearch\\ForceSearchIndex domain deerbase.wikibase.dev 0 1000
```

Note that the 1st parameter (`domain` in this example) could be any other DB column. The second argument is the value to match against. In this example this means: "Run this for the wiki with the domain 'deerbase.wikibase.dev'".

Parameter 3 and 4 represent a start and endpoint for the pages that should be indexed. If you don't know how many pages the wiki has (or if you want to limit server load), you can run it in batches, proceeding through the data until the job reports that it indexed 0 pages (example: first use `0 1000`, then `1000 2000`, and so on).

In this example case, the wiki only had 24 pages:
```
$ kubectl exec -ti deployments/api-app-backend -- php artisan job:dispatchNow CirrusSearch\\ForceSearchIndex domain deerbase.wikibase.dev 0 1000
[2023-04-26 15:16:01] production.INFO: App\Jobs\CirrusSearch\ForceSearchIndex::handleResponse: Finished batch! Indexed 24 pages. From id 0 to 1000  

$ kubectl exec -ti deployments/api-app-backend -- php artisan job:dispatchNow CirrusSearch\\ForceSearchIndex domain deerbase.wikibase.dev 30 1000
[2023-04-26 15:20:31] production.INFO: App\Jobs\CirrusSearch\ForceSearchIndex::handleResponse: Finished batch! Indexed 0 pages. From id 30 to 1000  
```
