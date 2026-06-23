<?php

namespace Tests\Support;

use App\Models\Siswa;
use Illuminate\Http\Request;

trait CreatesControllerRequests
{
    protected function requestWithValidatedData(array $validated, ?object $user = null): Request
    {
        $request = new class($validated) extends Request {
            public function __construct(private array $validatedData)
            {
                parent::__construct();
            }

            public function validate(array $rules, ...$params): array
            {
                return $this->validatedData;
            }
        };

        $request->setUserResolver(fn () => $user);

        return $request;
    }

    protected function requestWithInput(array $input, ?object $user = null): Request
    {
        $request = Request::create('/', 'POST', $input);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    protected function fakeUser(?Siswa $siswa = null, int $id = 99): object
    {
        return new class($siswa, $id) {
            public function __construct(public ?Siswa $siswa, public int $id)
            {
            }
        };
    }
}
