<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Branch;

use Gush\Command\Branch\BranchSyncCommand;
use Gush\Tests\Command\BaseTestCase;
use Gush\Tests\Fixtures\OutputFixtures;

class BranchSyncCommandTest extends BaseTestCase
{
    const TEST_BRANCH_NAME = 'test_branch';

    /**
     * @test
     */
    public function syncs_current_branch_with_origins_version()
    {
        $processHelper = $this->expectProcessHelper();
        $gitHelper = $this->expectGitHelper();
        $tester = $this->getCommandTester($command = new BranchSyncCommand());
        $command->getHelperSet()->set($processHelper, 'process');
        $command->getHelperSet()->set($gitHelper, 'git');

        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(OutputFixtures::BRANCH_SYNC, trim($tester->getDisplay(true)));
    }

    private function expectProcessHelper()
    {
        $processHelper = $this->getMock(
            'Gush\Helper\ProcessHelper',
            ['runCommands']
        );
        $processHelper->expects($this->once())
            ->method('runCommands')
            ->with([
                [
                    'line' => 'git remote update',
                    'allow_failures' => true
                ],
                [
                    'line' => 'git checkout '.self::TEST_BRANCH_NAME,
                    'allow_failures' => true
                ],
                [
                    'line' => 'git reset --hard HEAD~1',
                    'allow_failures' => true
                ],
                [
                    'line' => 'git pull -u origin '.self::TEST_BRANCH_NAME,
                    'allow_failures' => true
                ],
                [
                    'line' => 'git checkout '.self::TEST_BRANCH_NAME,
                    'allow_failures' => true
                ]
            ])
        ;

        return $processHelper;
    }

    private function expectGitHelper()
    {
        $gitHelper = $this
            ->getMockBuilder('Gush\Helper\GitHelper')
            ->disableOriginalConstructor()
            ->setMethods(['getBranchName'])
            ->getMock()
        ;
        $gitHelper->expects($this->once())
            ->method('getBranchName')
            ->will($this->returnValue(self::TEST_BRANCH_NAME))
        ;

        return $gitHelper;
    }
}
