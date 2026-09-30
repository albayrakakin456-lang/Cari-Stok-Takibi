<?php

namespace App\Http\Requests;

class UpdateCampaignRequest extends CampaignRequest
{
    // Güncellemede kampanya kodu benzersizlik kontrolünden mevcut kayıt hariç tutulur.
}
