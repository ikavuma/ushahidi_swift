<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Swiftriver\Core\Modules\SiSPS\Parsers;
class TwitterAccountParser implements IParser {
    /**
     * Implementation of IParser::GetAndParse
     * @param string[] $parameters
     * Required Parameter Values =
     *  'feedUrl' = The url to the RSS feed
     * @param datetime $lassucess
     */
    public function GetAndParse($parameters, $lastsucess) {
        $logger = \Swiftriver\Core\Setup::GetLogger();
        $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [Method invoked]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [START: Extracting required parameters]", \PEAR_LOG_DEBUG);

        //Extract the required variables
        $TwitterAccount = $parameters["TwitterAccount"];
        if(!isset($TwitterAccount) || ($TwitterAccount == "")) {
            $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [the parapeter 'TwitterAccount' was not supplued. Returning null]", \PEAR_LOG_DEBUG);
            $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [Method finished]", \PEAR_LOG_DEBUG);
            return null;
        }

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [END: Extracting required parameters]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [START: Constructing source object]", \PEAR_LOG_DEBUG);

        //Create the source that will be used by all the content items Passing in the feed uri which can
        //be used to uniquly identify the source of the content
        $source = \Swiftriver\Core\ObjectModel\ObjectFactories\SourceFactory::CreateSourceFromID($feedUrl);

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [END: Constructing source object]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [START: Including the SimplePie module]", \PEAR_LOG_DEBUG);

        //Include the Simple Pie Framework to get and parse feeds
        $config = \Swiftriver\Core\Setup::Configuration();
        include_once $config->ModulesDirectory."/SimplePie/simplepie.inc";

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [END: Including the SimplePie module]", \PEAR_LOG_DEBUG);

        //Construct a new SimplePie Parsaer
        $feed = new \SimplePie();

        //Get the cach directory
        $cacheDirectory = $config->CachingDirectory;

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [Setting the caching directory to $cacheDirectory]", \PEAR_LOG_DEBUG);

        //Set the caching directory
        $feed->set_cache_location($cacheDirectory);

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [Setting the feed url to $feedUrl]", \PEAR_LOG_DEBUG);


        //Twitter url combined with the account name passed to this feed.
        $TwitterUrl = "http://twitter.com/statuses/user_timeline/".$TwitterAccount.".rss";

        //Pass the feed URL to the SImplePie object
        $feed->set_feed_url($TwitterUrl);

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [Initilising the feed]", \PEAR_LOG_DEBUG);

        //Run the SimplePie
        $feed->init();

        //Create the Content array
        $contentItems = array();

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [START: Parsing feed items]", \PEAR_LOG_DEBUG);

        $tweets = $feed->get_items();

        if(!$tweets || $tweets == null || !is_array($tweets) || count($tweets) < 1) {
            $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [No feeditems recovered from the feed]", \PEAR_LOG_DEBUG);
        }

        //Loop throught the Feed Items
        foreach($tweets as $tweet) {
            //Extract the date of the content
            $contentdate =  strtotime($tweet->get_date());
            if(isset($lastsucess) && is_numeric($lastsucess) && isset($contentdate) && is_numeric($contentdate)) {
                if($contentdate < $lastsucess) {
                    $textContentDate = date("c", $contentdate);
                    $textLastSucess = date("c", $lastsucess);
                    $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [Skipped feed item as date $textContentDate less than last sucessful run ($textLastSucess)]", \PEAR_LOG_DEBUG);
                    continue;
                }
            }

            $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [Adding feed item]", \PEAR_LOG_DEBUG);

            //Extract all the relevant feedItem info
            $title = $tweet->get_title();
            $description = $tweet->get_description();
            $contentLink = $tweet->get_permalink();
            $date = $tweet->get_date();

            //Create a new Content item
            $item = \Swiftriver\Core\ObjectModel\ObjectFactories\ContentFactory::CreateContent($source);

            //Fill the Content Item
            $item->text[] = new \Swiftriver\Core\ObjectModel\LanguageSpecificText(
                    null, //here we set null as we dont know the language yet
                    $title,
                    array($description));
            $item->link = $contentLink;
            $item->date = strtotime($date);

            //Add the item to the Content array
            $contentItems[] = $item;
        }

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [END: Parsing feed items]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterAccountParser::GetAndParse [Method finished]", \PEAR_LOG_DEBUG);

        //return the content array
        return $contentItems;
    }
}
?>