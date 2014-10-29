<?php

    //Require base datasource
    require_once(EXTENSIONS . '/twitter/lib/data.base.php');

    //Retrieves the twitter favorites
    Class datasourceTwitter_favorites extends datasourceTwitter_base{

        //Cosntructor
        public function __construct(&$parent, $env=NULL, $process_params=true){
            //Set twitter mode
            $this->mode = self::MODE_FAVORITES;
            parent::__construct($parent, $env, $process_params);
        }

        //About
        public function about(){
            return array(
                'name' => 'Twitter: Favorites',
                'author' => array(
                    'name' => 'Brian Drum',
                    'website' => 'http://briandrum.net',
                    'email' => 'brian@briandrum.net'),
                'version' => '0.5.1'
            );
        }

        //Do not allow the editor parse this custom data-source
        public function allowEditorToParse(){
            return false;
        }

    }
