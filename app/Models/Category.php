<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public $timestamps = false;
    protected $fillable = ['title', 'path', 'level', 'parent_id'];

    protected static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub
        self::creating(function (Category $category) {
            if (is_null($category->parent_id)) {
                $category->level = 0;
                $category->path = '-';
            } else {
                $category->level = $category->parent->level = 1;
                $category->path = $category->parent->path . $category->parent_id . '-';
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    public function getCategoryTree($parent_id = null, $categories = null)
    {
        if (is_null($categories)) {
            $categories = Category::all();
        }

        return $categories->where('parent_id', $parent_id)->map(function (Category $category) use ($categories) {
            $data = ['id' => $category->id, 'title' => $category->title];

            if (!$category->is_directory){
                return $data;
            }

            $data['children']=$this->getCategoryTree($category->id,$categories);

            return $data;
        });
    }
}
