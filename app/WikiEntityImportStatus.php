<?php

namespace App;

enum WikiEntityImportStatus: string {
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
}
