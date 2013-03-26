<?php
/**
 * Ooba_Model_Searchable
 *
 * @category   Ooba
 * @package    Ooba_Model
 * @copyright (c) Copyright 2010-2012, Michael Faletti. All Rights Reserved.
 */

interface Ooba_Model_Searchable
{
    /**
     * Covert object to search data
     *
     * @return string
     **/
    public function toSearch();
    
    /**
     * Load data into model
     *
     * @param  array $data Data from search
     * @return Ooba_Model_Abstract
     **/
    public function fromSearch(array $data);

    /**
     * Get the last time the model was indexed
     *
     * @return date
     */
    public function getLastIndex();

    /**
     * Set the last time the model was indexed
     *
     * @param  mixed $date Date time.
     * @return void
     */
    public function setLastIndex($date);

}
