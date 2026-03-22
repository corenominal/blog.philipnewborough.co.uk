<?php

namespace App\Controllers\Admin;

use Hermawan\DataTables\DataTable;
use App\Models\PostModel;
use App\Models\TagModel;

class Home extends BaseController
{
    /**
     * Display the Admin Dashboard page.
     *
     * @return string Rendered admin dashboard view output.
     */
    public function index()
    {
        $postModel = new PostModel();
        $tagModel  = new TagModel();

        $data['stats'] = [
            'total_posts'     => $postModel->countAll(),
            'published_posts' => $postModel->where('status', 'published')->countAllResults(),
            'draft_posts'     => $postModel->where('status', 'draft')->countAllResults(),
            'trashed_posts'   => $postModel->where('status', 'trashed')->countAllResults(),
            'total_tags'      => $tagModel->countAllResults(),
            'total_views'     => (int) ($postModel->builder()->selectSum('hitcounter')->get()->getRow()->hitcounter ?? 0),
        ];

        $data['recent_posts'] = $postModel
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();

        $data['datatables'] = true;
        $data['js']         = ['admin/home'];
        $data['css']        = ['admin/home'];
        $data['title']      = 'Admin Dashboard';

        return view('admin/home', $data);
    }

    /**
     * JSON endpoint returning live dashboard stats.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function stats()
    {
        $postModel = new PostModel();
        $tagModel  = new TagModel();

        return $this->response->setJSON([
            'total_posts'     => $postModel->countAll(),
            'published_posts' => $postModel->where('status', 'published')->countAllResults(),
            'draft_posts'     => $postModel->where('status', 'draft')->countAllResults(),
            'trashed_posts'   => $postModel->where('status', 'trashed')->countAllResults(),
            'total_tags'      => $tagModel->countAllResults(),
            'total_views'     => (int) ($postModel->builder()->selectSum('hitcounter')->get()->getRow()->hitcounter ?? 0),
        ]);
    }

    /**
     * Server-side DataTables endpoint for the posts table.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface JSON response for DataTables.
     */
    public function postsDataTable()
    {
        $model   = new PostModel();
        $builder = $model->builder()->where('deleted_at IS NULL');

        $statusFilter = $this->request->getGet('status_filter');
        if (!empty($statusFilter)) {
            $builder->where('status', $statusFilter);
        }

        $statusMap = [
            'published' => 'success',
            'draft'     => 'secondary',
            'revision'  => 'warning',
            'trashed'   => 'danger',
        ];

        return DataTable::of($builder)
            ->add('url', function ($row) {
                return site_url('posts/' . esc($row->slug));
            })
            ->add('raw_status', function ($row) {
                return $row->status;
            })
            ->edit('title', function ($row) {
                if ($row->status === 'published') {
                    return '<a href="' . site_url('posts/' . esc($row->slug)) . '" target="_blank" class="text-decoration-none fw-semibold">' . esc($row->title) . '</a>';
                }
                return '<span class="fw-semibold">' . esc($row->title) . '</span>';
            })
            ->edit('status', function ($row) use ($statusMap) {
                $colour = $statusMap[$row->status] ?? 'secondary';
                return '<span class="badge text-bg-' . $colour . '">' . esc($row->status) . '</span>';
            })
            ->edit('tags', function ($row) {
                if (empty($row->tags)) {
                    return '—';
                }
                $tagList = array_filter(array_map('trim', explode(',', $row->tags)));
                $badges  = array_map(fn($t) => '<span class="badge text-bg-dark border me-1">' . esc($t) . '</span>', $tagList);
                return implode('', $badges);
            })
            ->edit('hitcounter', function ($row) {
                return number_format((int) $row->hitcounter);
            })
            ->edit('published_at', function ($row) {
                return $row->published_at ? date('d M Y', strtotime($row->published_at)) : '—';
            })
            ->toJson(true);
    }

    /**
     * Soft-delete selected posts.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function deletePosts()
    {
        $json = $this->request->getJSON(true);
        $ids  = $json['ids'] ?? [];

        $ids = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));

        if (empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'No valid IDs provided.',
            ]);
        }

        $model = new PostModel();

        // Split IDs into those already trashed (hard-delete) and others (mark as trashed)
        $alreadyTrashed = $model->where('status', 'trashed')->whereIn('id', $ids)->findAll();
        $trashedIds     = array_column($alreadyTrashed, 'id');
        $toTrashIds     = array_values(array_diff($ids, $trashedIds));

        if (!empty($toTrashIds)) {
            $model->whereIn('id', $toTrashIds)->set(['status' => 'trashed'])->update();
        }

        if (!empty($trashedIds)) {
            $model->whereIn('id', $trashedIds)->delete(null, true);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'trashed' => count($toTrashIds),
            'deleted' => count($trashedIds),
        ]);
    }
}
