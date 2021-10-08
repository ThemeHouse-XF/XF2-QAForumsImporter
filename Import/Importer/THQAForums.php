<?php


namespace ThemeHouse\QAForumsImporter\Import\Importer;


use ThemeHouse\QAForums\XF\Entity\Thread;
use Throwable;
use XF;
use XF\Import\StepState;
use XF\Timer;

/**
 * Class THQAForums
 * @package ThemeHouse\QAForumsImporter\Import\Importer
 */
class THQAForums extends AbstractQAForumsImporter
{
    public static function getListInfo()
    {
        return [
            'target' => 'XenForo 2.2 Question Threads',
            'source' => '[TH] Question & Answer Forums',
        ];
    }

    /**
     * @return array[]
     */
    public function getSteps()
    {
        return [
            'threadstatus' => [
                'title' => XF::phrase('thqafi_thread_status')
            ],
            'votes' => [
                'title' => XF::phrase('thqafi_votes')
            ]
        ];
    }

    /**
     * @return int
     */
    public function getStepEndVotes()
    {
        return $this->db()->fetchOne("
            SELECT
                MAX(vote_id)
            FROM
                xf_th_vote_qaforum
        ") ?: 0;
    }

    /**
     * @param StepState $state
     * @param array $stepConfig
     * @param $maxTime
     * @param int $limit
     * @return StepState
     */
    public function stepVotes(StepState $state, array $stepConfig, $maxTime, $limit = 50)
    {
        $timer = new Timer($maxTime);
        $votes = $this->getVotes($state->startAfter, $state->end, $limit);

        foreach ($votes as $oldId => $data) {
            $state->startAfter = $oldId;
            if (!$data) {
                continue;
            }
            unset($data['vote_id']);

            $vote = $this->em()->create('XF:ContentVote');
            $vote->bulkSet($data);

            try {
                $vote->save(false);
                $state->imported++;
            } catch (Throwable $e) {

            }

            if ($timer->limitExceeded()) {
                break;
            }
        }

        return $state->resumeIfNeeded();
    }

    /**
     * @param $startAfter
     * @param $end
     * @param $limit
     * @return array
     */
    protected function getVotes($startAfter, $end, $limit)
    {
        return $this->db()->fetchAllKeyed($this->db()->limit("
            SELECT
                vote.vote_id AS vote_id,
                'post' AS content_type,
                vote.post_id AS content_id,
                vote.user_id AS vote_user_id,
                post.user_id AS content_user_id,
                '1' AS is_content_user_counted,
                IF(vote.vote_type = 'up', '1', '-1') AS score,
                vote.vote_date AS vote_date
            FROM
                xf_th_vote_qaforum AS vote
            JOIN
                xf_post AS post USING(post_id)
            WHERE
                post.user_id
                AND vote.vote_id > ?
                AND vote.vote_id <= ?
            ORDER BY
                vote.vote_id
        ", $limit), 'vote_id', [$startAfter, $end]);
    }

    /**
     * @return int
     */
    public function getStepEndThreadstatus()
    {
        return $this->db()->fetchOne("
            SELECT
                MAX(thread_id)
            FROM
                xf_thread
            WHERE
                th_is_qa_qaforum
        ") ?: 0;
    }

    /**
     * @param StepState $state
     * @param array $stepConfig
     * @param $maxTime
     * @param int $limit
     * @return StepState
     */
    public function stepThreadstatus(StepState $state, array $stepConfig, $maxTime, $limit = 50)
    {
        $timer = new Timer($maxTime);
        $threadIds = $this->getThreadData($state->startAfter, $state->end, $limit);

        if (empty($threadIds)) {
            return $state->complete();
        }

        $bestAnswers = $this->db()->fetchPairs('
            SELECT
                thread_id,
                post_id
            FROM
                xf_post
            WHERE
                thread_id IN (' . join(',', array_keys($threadIds)) . ')
                AND xf_post.th_best_answer_qaforum
        ');

        foreach ($threadIds as $threadId => $threadData) {
            $state->startAfter = $threadId;

            /** @var Thread $thread */
            $thread = $this->em()->find('XF:Thread', $threadId);
            try {
                $thread->fastUpdate('discussion_type', 'question');
            } catch (\Exception $e) {
                $state->imported++;
            }

            if (isset($bestAnswers[$threadId])) {
                /** @var XF\Entity\Post $post */
                $post = $this->em()->find('XF:Post', $bestAnswers[$threadId]);
                /** @var XF\Service\ThreadQuestion\MarkSolution $service */
                $solutionService = $this->app->service('XF:ThreadQuestion\MarkSolution', $thread);
                $solutionService->setNotify(false);
                try {
                    $solutionService->markSolution($post);
                } catch (\Exception $e) {

                }
            }

            if ($timer->limitExceeded()) {
                break;
            }
        }

        return $state->resumeIfNeeded();
    }

    /**
     * @param $startAfter
     * @param $end
     * @param $limit
     * @return array
     */
    protected function getThreadData($startAfter, $end, $limit)
    {
        return $this->db()->fetchAllKeyed($this->db()->limit("
            SELECT
                thread.thread_id,
                th_is_qa_qaforum AS is_answered
            FROM
                xf_thread AS thread
            WHERE
                thread.th_is_qa_qaforum
                AND thread.thread_id > ?
                AND thread.thread_id <= ?
            ORDER BY
                thread.thread_id
            ", $limit), 'thread_id', [$startAfter, $end]);
    }
}