<?php declare(strict_types=1);

namespace Reconmap\Controllers\Tasks;

use Psr\Http\Message\ServerRequestInterface;
use Reconmap\Controllers\Controller;
use Reconmap\Models\AuditLogAction;
use Reconmap\Repositories\TaskRepository;
use Reconmap\Services\ActivityPublisherService;

class DeleteTaskController extends Controller
{
    public function __construct(private TaskRepository $repository)
    {
    }

    public function __invoke(ServerRequestInterface $request, array $args): array
    {
        $taskId = (int)$args['taskId'];

        $success = $this->repository->deleteById($taskId);

        $userId = $request->getAttribute('userId');
        $this->auditAction($userId, $taskId);

        return ['success' => $success];
    }

    private function auditAction(int $loggedInUserId, int $taskId): void
    {
        $activityPublisherService = $this->container->get(ActivityPublisherService::class);
        $activityPublisherService->publish($loggedInUserId, AuditLogAction::TASK_DELETED, ['type' => 'task', 'id' => $taskId]);
    }
}
