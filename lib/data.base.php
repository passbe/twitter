<?php

    //Require data-source
    require_once(TOOLKIT . '/class.datasource.php');

    //Require cachable
    require_once(CORE . '/class.cacheable.php');

    //Base datasource class
    Class datasourceTwitter_base extends Datasource{

        //Root node
        public $dsParamROOTELEMENT = 'twitter';

        //Tweet limit
        public $dsParamLIMIT = 20;

        //Cache timeout
        public $dsParamCACHE = 30;

        //Configuration key
        private $__name = 'twitter';

        //Contains twitter screen_name for mode params
        private $__screen_name = null;

        //Mode type
        public $mode = null;

        //Modes
        const MODE_HOME = 'home-timeline';
        const MODE_USER = 'user-timeline';
        const MODE_RETWEETS = 'retweets-of-me';
        const MODE_MENTIONS = 'mentions';
        const MODE_FAVORITES = 'favorites';

        //Single user object
        public $single_user = false;

        //Dependencies
        private $__dependencies = array(
            'twitter-async' => array(
                'EpiOAuth.php',
                'EpiTwitter.php',
                'EpiCurl.php'
            )
        );

        //Included json attributes in XML
        protected $_tweet_attributes = array(
            'id',
            'id_str',
            'created_at'
        );

        //Included json values in XML
        protected $_tweet_values = array(
            'text',
            'source',
            'in_reply_to_status_id',
            'in_reply_to_user_id',
            'in_reply_to_screen_name',
            'retweet_count',
            'favorited',
            'retweeted'
        );

        //Included user values in XML
        protected $_user_values = array(
            'id',
            'name',
            'screen_name',
            'location',
            'description',
            'url',
            'followers_count',
            'friends_count',
            'verified',
            'statuses_count',
            'profile_image_url',
            'profile_image_url_https'
        );

        //Retrieve elements
        public function grab(&$param_pool=NULL){
            //Create root XML node
            $result = new XMLElement($this->dsParamROOTELEMENT);

            //check that mode is set correctly
            if (!$this->checkMode()) {
                $result->appendChild(new XMLElement('error', 'Datasource twitter mode is set incorrectly.'));
                return $result;
            }

            //Set mode
            $result->setAttribute('mode', $this->mode);

            //Setup cacheable
            $cache = new Cacheable(Symphony::Database());
            $cache_id = md5($this->mode.$this->__name.$this->dsParamLIMIT);
            $cache_write = false;
            $cache_valid = false;

            //Check if there is any cache or if its too old
            $cached = $cache->check($cache_id);
            if ((!is_array($cached) || empty($cached)) || (time() - $cached['creation'] > ($this->dsParamCACHE * 60))) {
                //Only load the Dependencies if we need too
                $this->_dependencies = array();
                //Load twitter lib + first need to check if the git submodule has been init
                if (is_array($this->__dependencies)) {
                    foreach ($this->__dependencies as $k => $v) {
                        //check lib directory
                        if (is_dir(EXTENSIONS."/$this->__name/lib/$k")) {
                            if (is_array($v)) {
                                //include lib files
                                foreach ($v as $f) {
                                    //this will error if these files don't exist, user should re-check documentation
                                    //Note: We supress errors with include_once with @ to ensure we don't cause an error on the frontend
                                    @include_once(EXTENSIONS."/$this->__name/lib/$k/$f");
                                }
                            }
                        }
                    }
                }
                $cache_write = true;
            } else {
                $cache_valid = true;
            }

            //Get current configuration
            $this->__screen_name = Symphony::Configuration()->get('screen_name', $this->__name);

            //Use cache before anything else
            if ($cache_valid) {
                $data = unserialize($cached['data']);
                $creation = DateTimeObj::get('c', $cached['creation']);
            } else {
                //Get current configuration
                $consumer_key = Symphony::Configuration()->get('consumer_key', $this->__name);
                $consumer_secret = Symphony::Configuration()->get('consumer_secret', $this->__name);
                $oauth_token = Symphony::Configuration()->get('oauth_token', $this->__name);
                $oauth_secret = Symphony::Configuration()->get('oauth_secret', $this->__name);
                try {
                    //Setup twitter lib
                    $twitter = new EpiTwitter($consumer_key, $consumer_secret, $oauth_token, $oauth_secret);
                    //If successful
                    if ($twitter) {
                        $twitter->useApiVersion(1.1);
                        $twitter->useAsynchronous(false);
                        $response = $twitter->get($this->getModeURI(), $this->getModeParams());
                        //If response successful and results
                        if ($response->code == 200 && is_array($response->response)) {
                            //Set data
                            $data = $response->response;
                            $creation = DateTimeObj::get('c');
                            //Write cache
                            if ($cache_write) {
                                $cache->write($cache_id, serialize($data), $this->dsParamCACHE);
                            }
                        } else {
                            //Send back error
                            $result->appendChild(new XMLElement('error', 'An unknown error has occured.'));
                            return $result;
                        }
                    }
                } catch (Exception $e) {
                    $result->appendChild(new XMLElement('error', $e));
                    return $result;
                }
            }

            //If we have data process it
            if (isset($data) && isset($creation)) {
                //Set creation
                $result->setAttribute('creation', $creation);
                foreach ($data as $k => $tweet) {
                    //Create tweet node
                    $t = new XMLElement('tweet');
                    //Store each tweet attributes
                    foreach ($this->_tweet_attributes as $key) {
                        if (array_key_exists($key, $tweet)) {
                            $t->setAttribute($key, General::sanitize($tweet[$key]));
                        }
                    }
                    //Store each tweet value
                    foreach ($this->_tweet_values as $key) {
                        if (array_key_exists($key, $tweet)) {
                            $t->appendChild(new XMLElement($key, __(General::sanitize($tweet[$key]))));
                        }
                    }
                    //Store user details
                    if ((($this->single_user === true && $k == 0) || ($this->single_user === false)) && array_key_exists('user', $tweet)) {
                        $user = new XMLElement('user');
                        foreach ($this->_user_values as $key) {
                            if (array_key_exists($key, $tweet['user'])) {
                                $user->appendChild(new XMLElement($key, __(General::sanitize($tweet['user'][$key]))));
                            }
                        }
                        if ($this->single_user) {
                            //Only return single user object as its the same info
                            $result->appendChild($user);
                        } else {
                            //Return user object per tweet
                            $t->appendChild($user);
                        }
                    }
                    //Append tweet
                    $result->appendChild($t);
                }
            }
            //return result
            return $result;
        }

        //Check valid mode
        private function checkMode() {
            switch ($this->mode) {
                case self::MODE_HOME:
                case self::MODE_USER:
                case self::MODE_RETWEETS:
                case self::MODE_MENTIONS:
                case self::MODE_FAVORITES:
                    return true;
                break;
            }
            return false;
        }

        //Get twitter URI from mode
        private function getModeURI() {
            switch ($this->mode) {
                case self::MODE_HOME:
                    return '/statuses/home_timeline.json';
                break;
                case self::MODE_USER:
                    return '/statuses/user_timeline.json';
                break;
                case self::MODE_RETWEETS:
                    return '/statuses/retweets_of_me.json';
                break;
                case self::MODE_MENTIONS:
                    return '/statuses/mentions_timeline.json';
                break;
                case self::MODE_FAVORITES:
                    return '/favorites/list.json';
                break;
            }
            return false;
        }

        //Get twitter API params for each mode
        private function getModeParams() {
            switch ($this->mode) {
                case self::MODE_USER:
                case self::MODE_FAVORITES:
                    return array('screen_name' => $this->__screen_name, 'count' => $this->dsParamLIMIT);
                break;
                case self::MODE_HOME:
                case self::MODE_RETWEETS:
                case self::MODE_MENTIONS:
                    return array('count' => $this->dsParamLIMIT);
                break;
            }
            return array();
        }

    }
