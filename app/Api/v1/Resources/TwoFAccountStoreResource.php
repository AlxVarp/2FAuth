<?php

namespace App\Api\v1\Resources;

use App\Facades\IconStore;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $otp_type
 * @property string $account
 * @property string $service
 * @property string $icon
 * @property string $secret
 * @property int $digits
 * @property string $algorithm
 * @property int|null $period
 * @property int|null $counter
 */
class TwoFAccountStoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = $request->user();
        $isAdmin = $user && method_exists($user, 'isAdministrator') ? $user->isAdministrator() : false;

        $shouldExposeSecret = $isAdmin && (
            ! $request->has('withSecret') ||
            (int) filter_var($request->input('withSecret'), FILTER_VALIDATE_BOOLEAN) == 1
        );

        return [
            'otp_type' => $this->otp_type,
            'account'  => $this->account,
            'service'  => $this->service,
            'icon'     => $this->icon && IconStore::exists($this->icon) ? $this->icon : null,
            'secret'   => $shouldExposeSecret ? $this->secret : null,
            'digits'    => $isAdmin ? (int) $this->digits : null,
            'algorithm' => $isAdmin ? $this->algorithm : null,
            'period'   => $isAdmin
                ? (is_null($this->period) ? null : (int) $this->period)
                : null,
            'counter'  => $isAdmin
                ? (is_null($this->counter) ? null : (int) $this->counter)
                : null,
        ];
    }
}
