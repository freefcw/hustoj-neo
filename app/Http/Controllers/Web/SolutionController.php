<?php

namespace App\Http\Controllers\Web;

use App\Entities\Problem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Solution\IndexRequest;
use App\Repositories\Criteria\OrderBy;
use App\Repositories\Criteria\Where;
use App\Repositories\ProblemRepository;
use App\Repositories\SolutionRepository;
use App\Services\UserService;
use App\Status;
use App\Task\SolutionServer;
use Czim\Repository\Criteria\Common\WithRelations;

class SolutionController extends Controller
{
    public function index(IndexRequest $request)
    {
        /** @var SolutionRepository $repository */
        $repository = app(SolutionRepository::class);

        if ($request->getUserName()) {
            $user = app(UserService::class)->findByName($request->getUserName());
            $repository->pushCriteria(new Where('user_id', $user->id));
        }

        if ($request->getProblemId()) {
            $repository->pushCriteria(new Where('problem_id', $request->getProblemId()));
        }

        if ($request->getLanguage() != -1) {
            $filter = new Where('language', $request->getLanguage());
            $repository->pushCriteria($filter);
        }

        if ($request->getStatus() != -1 && $request->getStatus()) {
            $filter = new Where('result', $request->getStatus());
            $repository->pushCriteria($filter);
        }

        $per_page = 100;
        $repository->pushCriteria(new WithRelations(['user']));
        $repository->pushCriteria(new OrderBy('id', 'desc'));
        $solutions = $repository->paginate($per_page);

        return view('web.solution.index')->with('solutions', $solutions);
    }

    public function create($id)
    {
        if (! auth()->user()) {
            return redirect(route('problem.view', ['problem' => $id]))->withErrors(__('Login first'));
        }

        /** @var Problem $problem */
        $problem = app(ProblemRepository::class)->findOrFail($id);

        if (! config('hustoj.special_judge_enabled') && $problem->isSpecialJudge()) {
            return redirect(route('problem.view', ['problem' => $id]))
                ->withErrors(__('Special judge current disabled!'));
        }

        return view('web.problem.submit', ['problem' => $problem]);
    }

    public function store()
    {
        $data = [
            'user_id'     => app('auth')->guard()->id(),
            'problem_id'  => request('problem_id', 0),
            'language'    => request('language'),
            'ip'          => request()->ip(),
            'order'       => request('order', 0),
            'contest_id'  => request('contest_id', 0),
            'code_length' => strlen(request('code')),
            'result'      => Status::PENDING,
        ];

        /** @var SolutionRepository $repository */
        $repository = app(SolutionRepository::class);
        /** @var \App\Entities\Solution $solution */
        $solution = $repository->create($data);
        $solution->source()->create([
            'code' => request('code', ''),
        ]);

        app(SolutionServer::class)->add($solution)->send();

        return redirect(route('solution.index'));
    }

    public function source($id)
    {
        /** @var \App\Entities\Solution $solution */
        $solution = app(SolutionRepository::class)->findOrFail($id);
        if (! can_view_code($solution)) {
            return redirect(route('solution.index'))->withErrors(__('You have no permission access solution source'));
        }

        return view('web.solution.source')->with('solution', $solution);
    }

    public function compileInfo($id)
    {
        /** @var \App\Entities\Solution $solution */
        $solution = app(SolutionRepository::class)->findOrFail($id);
        if (! can_view_code($solution)) {
            return back()->withErrors(__('You cannot access this solution'));
        }

        return view('web.solution.compile_info')->with('solution', $solution);
    }

    public function runtimeInfo($id)
    {
        /** @var \App\Entities\Solution $solution */
        $solution = app(SolutionRepository::class)->findOrFail($id);
        if (! can_view_code($solution)) {
            return back()->withErrors(__('You cannot access this solution'));
        }

        return view('web.solution.runtime_info')->with('solution', $solution);
    }
}
