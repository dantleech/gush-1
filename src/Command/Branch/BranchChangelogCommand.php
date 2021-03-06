<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Branch;

use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BranchChangelogCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:changelog')
            ->setDescription('Reports what got fixed or closed since last release on current branch')
            ->setHelp(
                <<<EOF
Reports what got fixed or closed since last release on current branch.
reference: http://www.lornajane.net/posts/2014/github-powered-changelog-scripts

The <info>%command.name%</info> command :

    <info>$ gush %command.name%</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $latestTag = $this->getHelper('git')->runGitCommand('git describe --abbrev=0 --tags');
        } catch (\RuntimeException $e) {
            $output->writeln('<info>There were no tags found</info>');

            return self::COMMAND_SUCCESS;
        }

        $commits = $this->getHelper('git')->runGitCommand(
            sprintf('git log %s...HEAD --oneline', $latestTag)
        );

        // Filter commits that reference an issue
        $issues = [];

        $adapter = $this->getIssueTracker();

        foreach (explode(PHP_EOL, $commits) as $commit) {
            // Cut issue id from branch name (merge commits)
            if (preg_match('/\/([0-9]+)/i', $commit, $matchesGush) && isset($matchesGush[1])) {
                $issues[] = $matchesGush[1];
            }

            // Cut issue id from commit message
            if (preg_match('/[close|closes|fix|fixes] #([0-9]+)/i', $commit, $matchesGitRepo)
                && isset($matchesGitRepo[1])
            ) {
                $issues[] = $matchesGitRepo[1];
            }
        }

        sort($issues);

        foreach ($issues as $id) {
            $issue = $adapter->getIssue($id);

            $output->writeln(
                sprintf("%s: %s   <info>%s</info>", $id, $issue['title'], $issue['url'])
            );
        }

        return self::COMMAND_SUCCESS;
    }
}
