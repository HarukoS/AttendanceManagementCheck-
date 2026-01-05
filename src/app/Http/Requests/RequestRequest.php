<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class RequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_start' => ['required', 'date_format:H:i'],
            'work_end'   => ['required', 'date_format:H:i'],

            'rests.*.rest_start' => ['nullable', 'date_format:H:i'],
            'rests.*.rest_end'   => ['nullable', 'date_format:H:i'],

            'reason' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'work_start.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'work_end.required'   => '出勤時間もしくは退勤時間が不適切な値です',

            'work_start.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'work_end.date_format'   => '出勤時間もしくは退勤時間が不適切な値です',

            'rests.*.rest_start.date_format' => '休憩時間が不適切な値です',
            'rests.*.rest_end.date_format'   => '休憩時間が不適切な値です',

            'reason.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $workStart = $this->work_start
                ? Carbon::createFromFormat('H:i', $this->work_start)
                : null;

            $workEnd = $this->work_end
                ? Carbon::createFromFormat('H:i', $this->work_end)
                : null;

            // ① 出勤 > 退勤 / 退勤 < 出勤
            if ($workStart && $workEnd && $workStart->gte($workEnd)) {
                $validator->errors()->add(
                    'work_start',
                    '出勤時間もしくは退勤時間が不適切な値です'
                );
            }

            // ②③ 休憩時間チェック
            if ($this->rests && $workStart && $workEnd) {
                foreach ($this->rests as $index => $rest) {

                    $restStart = !empty($rest['rest_start'])
                        ? Carbon::createFromFormat('H:i', $rest['rest_start'])
                        : null;

                    $restEnd = !empty($rest['rest_end'])
                        ? Carbon::createFromFormat('H:i', $rest['rest_end'])
                        : null;

                    // ② 休憩開始が出勤前 or 退勤後
                    if (
                        $restStart &&
                        ($restStart->lt($workStart) || $restStart->gt($workEnd))
                    ) {

                        $validator->errors()->add(
                            "rests.$index.rest_start",
                            '休憩時間が不適切な値です'
                        );
                    }

                    // ③ 休憩終了が退勤後
                    if ($restEnd && $restEnd->gt($workEnd)) {
                        $validator->errors()->add(
                            "rests.$index.rest_end",
                            '休憩時間もしくは退勤時間が不適切な値です'
                        );
                    }
                }
            }
        });
    }
}
