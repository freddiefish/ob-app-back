<?php 

class FirstCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function fronpageWorks(AcceptanceTester $I)
    {
            $I->amOnPage('/');
            $I->see('Home');
    }
}
