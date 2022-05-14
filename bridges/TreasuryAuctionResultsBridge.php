<?php
class TreasuryAuctionResultsBridge extends FeedExpander {

    const MAINTAINER = 'Kevin Saylor';
    const NAME = 'Treasury Auction Results Bridge';
    const URI = 'https://www.treasurydirect.gov/TA_WS/securities/auctioned/rss';
    const DESCRIPTION = 'provides treasure auction results from US Treasury';
    const PARAMETERS = array();
    const CACHE_TIMEOUT = 3600;

    public function collectData(){
        $this->collectExpandableDatas('your feed URI');
    }
}