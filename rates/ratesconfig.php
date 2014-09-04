<?php

error_reporting(~0); ini_set('display_errors', 1);

Class ratesConfig {

    function config() {
        $options['api_key_cryptorush'] = 'apikey';
        $options['api_key_swissecex'] = 'apikey';
        $options['user_id_cryptorush'] = 'userid';
        return $options;
    }

}

Class rDbConfig {
    
    function config() {
        $itm['driver'] = 'mysql';
        $itm['host'] = '';
        $itm ['database'] = '';
        $itm['username'] = '';
        $itm['password'] = '';
    
        if(isset($itm)) {
            return $itm;
        } else {
            return 'false';
        }
    }

}


?>
