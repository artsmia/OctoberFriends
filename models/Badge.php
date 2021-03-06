<?php namespace DMA\Friends\Models;

use Model;
use RainLab\User\Models\User;
use DMA\Friends\Models\Bookmark;
use Smirik\PHPDateTimeAgo\DateTimeAgo as TimeAgo;
use Auth;
use DB;

/**
 * Badge Model
 */
class Badge extends Model
{

    use \October\Rain\Database\Traits\Validation;
    use \DMA\Friends\Traits\Rateable;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'dma_friends_badges';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['touch'];

    protected $dates = ['date_begin', 'date_end'];

    public $rules = [ 
        'title' => 'required',
    ]; 

    /**
     * @var array Relations
     */
    public $hasMany = [
        'steps' => ['DMA\Friends\Models\Step'],
    ];
    public $belongsToMany = [
        'users' => ['RainLab\User\Models\User', 'table' => 'dma_friends_badge_user'],
    ];
    public $attachOne = [
        'image' => ['System\Models\File']
    ];

    public $morphMany = [
        'activityLogs'  => ['DMA\Friends\Models\ActivityLog', 'name' => 'object'],
        'bookmarks'     => ['DMA\Friends\Models\Bookmark', 'name' => 'object'],
    ];

    public $morphToMany = [
        'categories'    => ['DMA\Friends\Models\Category', 
            'name'  => 'object', 
            'table' => 'dma_friends_object_categories',
            'order' => 'name',
        ],
    ];

    public function scopeNotCompleted($query, User $user)
    {
        return $query->join('dma_friends_badge_user', 'dma_friends_badges.id', '=', 'dma_friends_badge_user.badge_id')
            ->where('dma_friends_badge_user.user_id', '!=', $user->id)
            ->groupBy('dma_friends_badges.id');
    }

    public function scopefindWordpress($query, $id)
    {   
        return $query->where('wordpress_id', $id);
    }  

    /**
     * Return only badage that are active
     */
    public function scopeIsActive($query)
    {
        return $query->where('is_published', true)
                     ->where('is_archived',  false);
    }
    
    
    /**
     * Mutator function to return the pivot timestamp as time ago
     * @return string 
     * The time since the badge was earned
     */
    public function getTimeAgoAttribute($value)
    {
        if (!isset($this->pivot->created_at)) return null;

        $timeAgo = new TimeAgo;
        return $timeAgo->get($this->pivot->created_at);
    }

    /**
     * Mutator to return the number of times a user has earned a badge
     * @return int 
     * The number of times the authenticated user has earned the badge
     */
    public function getUserCountAttribute($value)
    {
        $user   = Auth::getUser();
        $count  = $user->badges()->where('badge_id', '=', $this->id)->count();

        return $count;
    }

    /**
     * Mutator to return the percentage of steps completed by the authenticated user
     * @return int
     * percentage of steps complete
     */
    public function getUserProgressAttribute()
    {
        $user = Auth::getUser();
        $ids = [];
        $stepCount = 0;

        foreach($this->steps as $step) {
            $ids[] = $step->id;
        }

        if ($ids && $this->userCount) {
            $stepCount = DB::table('dma_friends_step_user')
                ->whereIn('step_id', $ids)
                ->where('user_id', $user->id)
                ->count();

            $stepCount = $stepCount / $this->userCount;
        }
        
        if ($stepCount)
            return (count($this->steps) / $stepCount) * 100;
        
        return 0;
    }

    public function getIsBookmarkedAttribute()
    {
        $user = Auth::getUser();
        return (boolean)Bookmark::findBookmark($user, $this);
    }
}
