<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Swiftriver\Core\Modules\SiSPS\Parsers;
class EmailParser {//implements IParser {
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
        $logger->log("Core::Modules::SiSPS::Parsers::EmailParser::GetAndParse [Method invoked]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::EmailParser::GetAndParse [START: Extracting required parameters]", \PEAR_LOG_DEBUG);

        //Extract the required variables
        $email_ssl = $parameters["email_ssl"];
        $email_servertype = $parameters["email_servertype"];
        $email_host = $parameters["email_host"];
        $email_port = $parameters["email_port"];
        $email_username = $parameters["email_username"];
        $email_password = $parameters["email_password"];
        
        if(!isset( $email_username) || $email_username == "" ||
           !isset( $email_password) || $email_password == "" ||
           !isset( $email_host) || $email_host == "" ||
           !isset(  $email_port) || $email_port == "" ) {
            $logger->log("Core::Modules::SiSPS::Parsers::EmailParser::GetAndParse [one of the parameters 'email_ssl,email_servertype,email_host,email_port,email_username,email_password' was not supplued. Returning null]", \PEAR_LOG_DEBUG);
            $logger->log("Core::Modules::SiSPS::Parsers::EmailParser::GetAndParse [Method finished]", \PEAR_LOG_DEBUG);
            return null;
        }

        $logger->log("Core::Modules::SiSPS::Parsers::EmailParser::GetAndParse [END: Extracting required parameters]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::EmailParser::GetAndParse [START: Constructing source object]", \PEAR_LOG_DEBUG);

        //Create the source that will be used by all the content items Passing in the Search keyword which can
        //be used to uniquly identify the source of the content
        $source = \Swiftriver\Core\ObjectModel\ObjectFactories\SourceFactory::CreateSourceFromID($email_username.$email_host);

        $logger->log("Core::Modules::SiSPS::Parsers::EmailParser::GetAndParse [END: Constructing source object]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::EmailParser::GetAndParse [START: calling Imap module to get emails.]", \PEAR_LOG_DEBUG);


        //Include the Imap Module to get and parse emails.
        $config = \Swiftriver\Core\Setup::Configuration();
        include_once $config->ModulesDirectory."/Imap/Imap.php";


        $check_email = new Imap($email_ssl,$email_servertype,$email_host,$email_port,$email_username,$email_password);
        $messages = $check_email->get_messages();

	// Close Connection
	$check_email->close();
	
        // Add Messages
        $this->add_email($messages);

        $logger->log("Core::Modules::SiSPS::Parsers::EmailParser::GetAndParse [END: Parsing email items]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::EmailParser::GetAndParse [Method finished]", \PEAR_LOG_DEBUG);

        //return the content array
        return $this->Contentitems;
    }


    private function add_email($messages)
    {
        if (empty($messages) || !is_array($messages)) {
                return false;
        }

            foreach($messages as $message)
            {
                //Extract the date of the content
                $contentdate =  strtotime($message['date']);
                if(isset($lastsucess) && is_numeric($lastsucess) && isset($contentdate) && is_numeric($contentdate)) {
                    if($contentdate < $lastsucess) {
                        $textContentDate = date("c", $contentdate);
                        $textLastSucess = date("c", $lastsucess);
                        $logger->log("Core::Modules::SiSPS::Parsers::EmailParser::GetAndParse [Skipped email item as date $textContentDate less than last sucessful run ($textLastSucess)]", \PEAR_LOG_DEBUG);
                        continue;
                    }
                }
                
                $logger->log("Core::Modules::SiSPS::Parsers::EmailParser::GetAndParse [Adding feed item]", \PEAR_LOG_DEBUG);

                //Setup the variables to be used in the content item.
                $title = $message['subject'];
                $description = $message['body'];
                $contentLink = $message['email'];
                $date = $message['date'];
                $user_id = $message['email'];
                $email_id = $message['message_id'];
                $langcode = null;//here we set null as we dont know the language yet

                //Create a new Content item
                $item = \Swiftriver\Core\ObjectModel\ObjectFactories\ContentFactory::CreateContent($source);

                //Fill the Content Item
                $item->text[] = new \Swiftriver\Core\ObjectModel\LanguageSpecificText(
                        $langcode, 
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