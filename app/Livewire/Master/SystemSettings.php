<?php

namespace App\Livewire\Master;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Setting;
use App\Services\ImageService;
use App\Support\AturanGambar;

class SystemSettings extends Component
{
    use WithFileUploads;

    public $pos_background_image;
    public $pos_background_preview;

    public function mount()
    {
        $this->pos_background_preview = Setting::getValue('pos_background_image');
    }

    public function updatedPosBackgroundImage()
    {
        $this->validate($this->aturanBackground(), AturanGambar::messages('pos_background_image'));
    }

    public function simpanBackground()
    {
        if ($this->pos_background_image) {
            $this->validate($this->aturanBackground(), AturanGambar::messages('pos_background_image'));

            // Di-encode ulang (bukan disimpan apa adanya) dengan nama acak buatan server
            $path = app(ImageService::class)->simpanBackgroundPos($this->pos_background_image);
            $this->pos_background_image->delete();

            Setting::setValue('pos_background_image', '/storage/' . $path);
            $this->pos_background_preview = '/storage/' . $path;
            $this->pos_background_image = null;

            session()->flash('sukses', 'Background POS berhasil diperbarui!');
        }
    }

    // jpg/png/webp, maks 5MB & 16MP (lihat App\Support\AturanGambar)
    private function aturanBackground(): array
    {
        return ['pos_background_image' => AturanGambar::rules()];
    }

    public function hapusBackground()
    {
        Setting::setValue('pos_background_image', null);
        $this->pos_background_preview = null;
        $this->pos_background_image = null;

        session()->flash('sukses', 'Background POS dikembalikan ke default.');
    }

    public function render()
    {
        return view('livewire.master.system-settings');
    }
}
