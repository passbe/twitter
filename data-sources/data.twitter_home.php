<?php

    //Require base datasource
    require_once(EXTENSIONS . '/twitter/lib/data.base.php');

    //Retrieves the twitter users home timeline
    Class datasourceTwitter_home extends datasourceTwitter_base{

        //Cosntructor
        public function __construct(&$parent, $env=NULL, $process_params=true){
            //Set twitter mode
            $this->mode = self::MODE_HOME;
            parent::__construct($parent, $env, $process_params);
        }

        //About
        public function about(){
            return array(
                'name' => 'Twitter: Timeline',
                'author' => array(
                    'name' => 'Ben Passmore',
                    'website' => 'http://passbe.com',
                    'email' => 'contact@passbe.com'),
                'version' => '0.5.1'
            );
        }

        //Do not allow the editor parse this custom data-source
        public function allowEditorToParse(){
            return false;
        }

    }
