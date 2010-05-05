<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Swiftriver\Core\Modules\SiSPS\Parsers;
class TwitterSearchParser implements IParser {
    /**
     * Implementation of IParser::GetAndParse
     * @param string[] $parameters
     * Required Parameter Values =
     *  'feedUrl' = The url to the RSS feed
     * @param datetime $lassucess
     */

    private $Contentitems = array();

    public function GetAndParse($parameters, $lastsucess) {
        $logger = \Swiftriver\Core\Setup::GetLogger();
        $logger->log("Core::Modules::SiSPS::Parsers::TwitterSearchParser::GetAndParse [Method invoked]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterSearchParser::GetAndParse [START: Extracting required parameters]", \PEAR_LOG_DEBUG);

        //Extract the required variables
        $SearchKeyword = $parameters["SearchKeyword"];
        if(!isset($SearchKeyword) || ($SearchKeyword == "")) {
            $logger->log("Core::Modules::SiSPS::Parsers::TwitterSearchParser::GetAndParse [the parapeter 'feedUrl' was not supplued. Returning null]", \PEAR_LOG_DEBUG);
            $logger->log("Core::Modules::SiSPS::Parsers::TwitterSearchParser::GetAndParse [Method finished]", \PEAR_LOG_DEBUG);
            return null;
        }

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterSearchParser::GetAndParse [END: Extracting required parameters]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterSearchParser::GetAndParse [START: Constructing source object]", \PEAR_LOG_DEBUG);

        //Create the source that will be used by all the content items Passing in the feed uri which can
        //be used to uniquly identify the source of the content
        $source = \Swiftriver\Core\ObjectModel\ObjectFactories\SourceFactory::CreateSourceFromID($SearchKeyword);

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterSearchParser::GetAndParse [END: Constructing source object]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterSearchParser::GetAndParse [START: calling twitter API to get feeds.]", \PEAR_LOG_DEBUG);

        $page = 1;
        $have_results = TRUE; //just starting us off as true, although there may be no results
        while($have_results == TRUE && $page <= 5)
        { //This loop is for pagination of rss results
        $hashtag = trim(str_replace('#','',$SearchKeyword));
                $twitter_url = 'http://search.twitter.com/search.json?';
                $twitter_postfields = 'q=%23'.$hashtag.'&page='.$page;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_URL,$twitter_url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $twitter_postfields);
                $buffer=curl_exec($ch);
                //$have_results = $this->add__sontweets($buffer,$hashtag); //if FALSE, we will drop out of the loop
                $this->add_json_tweets($buffer,$lastsucess,$source); //if FALSE, we will drop out of the loop
                $page++;
        }

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterSearchParser::GetAndParse [END: Parsing feed items]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::TwitterSearchParser::GetAndParse [Method finished]", \PEAR_LOG_DEBUG);

        //return the content array
        return $this->Contentitems;
    }

    private function add_json_tweets($data,$lastsucess,$source)
    {
        $logger = \Swiftriver\Core\Setup::GetLogger();
        $tweets = json_decode($data, false);

        if(!$tweets || $tweets == null || !is_array($tweets) || count($tweets) < 1) {
            $logger->log("Core::Modules::SiSPS::Parsers::TwitterSearchParser::GetAndParse [No feeditems recovered from the feed]", \PEAR_LOG_DEBUG);
             return false;
         }

        if (array_key_exists('results', $tweets)) {
                $tweets = $tweets->{'results'};
        }
        foreach($tweets as $tweet)
        {
                        //Extract the date of the content
            $contentdate =  strtotime($tweet->{'created_at'});
            if(isset($lastsucess) && is_numeric($lastsucess) && isset($contentdate) && is_numeric($contentdate)) {
                if($contentdate < $lastsucess) {
                    $textContentDate = date("c", $contentdate);
                    $textLastSucess = date("c", $lastsucess);
                    $logger->log("Core::Modules::SiSPS::Parsers::TwitterSearchParser::GetAndParse [Skipped feed item as date $textContentDate less than last sucessful run ($textLastSucess)]", \PEAR_LOG_DEBUG);
                    continue;
                }
            }
                $logger->log("Core::Modules::SiSPS::Parsers::TwitterSearchParser::GetAndParse [Adding feed item]", \PEAR_LOG_DEBUG);

                $title = $tweet->{'text'};
                $description = $tweet->{'text'};
                $contentLink = $tweet->{'source'};
                $date = $tweet->{'created_at'};
                $tweet_user_id = $tweet->{'from_user_id'};
                $tweet_id = $tweet->{'id'};
               // $langcode = $tweet->{'language'};
                 //Create a new Content item
                $item = \Swiftriver\Core\ObjectModel\ObjectFactories\ContentFactory::CreateContent($source);

                //Fill the Content Item
                $item->text[] = new \Swiftriver\Core\ObjectModel\LanguageSpecificText(
                       null ,// $langcode, //here we set null as we dont know the language yet
                        $title,
                        array($description));
                $item->link = $contentLink;
                $item->date = strtotime($date);

                //Add the item to the Content array
                $this->Contentitems[] = $item;
        }
        return true;

    }




}
?>