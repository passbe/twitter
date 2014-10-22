<?php

    //Require base datasource
    require_once(EXTENSIONS . '/twitter/lib/data.base.php');

    //Retrieves the twitter users timeline
    Class datasourceTwitter_user extends datasourceTwitter_base{

        //Cosntructor
        public function __construct(&$parent, $env=NULL, $process_params=true){
            //Set twitter mode
            $this->mode = self::MODE_USER;
            //Only return one user object
            $this->single_user = true;
            parent::__construct($parent, $env, $process_params);
        }

        //About
        public function about(){
            return array(
                'name' => 'Twitter: User Tweets',
                'author' => array(
                    'name' => 'Ben Passmore',
                    'website' => 'http://passbe.com',
                    'email' => 'contact@passbe.com'),
                'version' => '0.5'
            );
        }

        //Do not allow the editor parse this custom data-source
        public function allowEditorToParse(){
            return false;
        }

    }
