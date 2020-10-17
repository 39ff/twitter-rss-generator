<?php
header("Content-Type: application/xml; charset=UTF-8");

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Laminas\Feed\Writer\Feed;
require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();


$app->get('/search', function (Request $request, Response $response, $args) {
    $aTwitterClient = new koulab\UltimateTwitter\Client();
    $aSearchResultBody = $aTwitterClient->get('https://api.twitter.com/1.1/search/tweets.json',[
        'query'=>array_merge($request->getQueryParams(),['tweet_mode'=>'extended','result_type'=>'recent'])
    ]);
    $decode = json_decode($aSearchResultBody);

    $feed = new Feed;
    $feed->setTitle("Twitter:".http_build_query($request->getQueryParams()));
    $feed->setDescription("Twitter Results");
    $feed->setDateModified(time());
    $feed->setFeedLink("http://example.com/atom",'atom');
    $query = $request->getQueryParams();
    $feed->setLink('https://twitter.com/search?'.http_build_query([
            'q'=>$query['q'],
            'src'=>'typed_query',
            'f'=>'live'
        ]));

    foreach($decode->statuses as $status){
        $entry = $feed->createEntry();
        $entry->setTitle(mb_strimwidth($status->full_text,0,50,'..'));
        $entry->setLink('https://twitter.com/'.$status->user->screen_name.'/status/'.$status->id_str);
        $entry->addAuthor([
            'name'  => $status->user->name,
        ]);
        $entry->setDateCreated(strtotime($status->created_at));
        $entry->setDateModified(strtotime($status->created_at));
        $entry->setDescription($status->full_text);
        $entry->setContent('<blockquote class="twitter-tweet" data-dnt="true"><a href="https://twitter.com/'.$status->user->screen_name.'/status/'.$status->id_str.'"></blockquote><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>');
        $feed->addEntry($entry);
    }
    $response->getBody()->write($feed->export('rss'));
    return $response;
});

$app->run();