<?php
declare(strict_types=1);

/** Escape all untrusted text before inserting it into an HTML context. */
function e(mixed $value): string {
    if (!is_scalar($value) && $value !== null) return '';
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function input(array $data, string $key): string {
    $value = $data[$key] ?? '';
    if (!is_string($value) && !is_int($value)) {
        throw new InvalidArgumentException('Invalid value for ' . str_replace('_', ' ', $key) . '.');
    }
    return trim((string)$value);
}
function required_text(array $data, string $key, int $max): string {
    $value = input($data, $key);
    if ($value === '' || mb_strlen($value) > $max) {
        throw new InvalidArgumentException(ucfirst(str_replace('_', ' ', $key)) . ' is required and must not exceed ' . $max . ' characters.');
    }
    return $value;
}
function integer_value(string $value, string $label, int $min, int $max): int {
    if (!preg_match('/^\d+$/D', $value) || strlen($value) > 10 || (int)$value < $min || (int)$value > $max) {
        throw new InvalidArgumentException($label . ' must be a whole number between ' . $min . ' and ' . $max . '.');
    }
    return (int)$value;
}
function valid_date(string $value, string $label, bool $optional = false): ?string {
    if ($optional && $value === '') return null;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) || $date->format('Y-m-d') !== $value || $value < '1900-01-01' || $value > '2100-12-31') {
        throw new InvalidArgumentException($label . ' must be a valid date between 1900 and 2100.');
    }
    return $value;
}
/** Integer pence avoids floating-point rounding in financial totals. */
function to_pence(string $amount): int {
    if (!preg_match('/^(\d{1,7})(?:\.(\d{1,2}))?$/D', $amount, $m)) {
        throw new InvalidArgumentException('Cost must be a non-negative amount with at most two decimal places.');
    }
    return (int)$m[1] * 100 + (int)str_pad($m[2] ?? '', 2, '0');
}
function money(int $pence): string { return '£' . number_format($pence / 100, 2); }
function date_status(?string $date, string $today): string {
    if ($date === null || $date === '') return 'Not recorded';
    if ($date < $today) return 'Overdue';
    if ($date <= (new DateTimeImmutable($today))->modify('+30 days')->format('Y-m-d')) return 'Due soon';
    return 'Current';
}
function service_status(array $vehicle, string $today): string {
    $date = $vehicle['next_service_date'];
    $target = $vehicle['next_service_mileage'];
    if ($date === null && $target === null) return 'Not recorded';
    if (($date !== null && $date < $today) || ($target !== null && (int)$vehicle['mileage'] >= (int)$target)) return 'Overdue';
    if (($date !== null && date_status($date, $today) === 'Due soon') || ($target !== null && (int)$target - (int)$vehicle['mileage'] <= 500)) return 'Due soon';
    return 'Current';
}
function vehicle_values(array $data): array {
    $registration = strtoupper(preg_replace('/\s+/', '', required_text($data, 'registration', 20)) ?? '');
    if (!preg_match('/^[A-Z0-9]{2,12}$/D', $registration)) throw new InvalidArgumentException('Registration must contain 2 to 12 letters or digits.');
    $status = input($data, 'status');
    if (!in_array($status, ['Available', 'In Use', 'Repair'], true)) throw new InvalidArgumentException('Invalid vehicle status.');
    $target = input($data, 'next_service_mileage');
    return [
        'registration'=>$registration, 'make'=>required_text($data,'make',60), 'model'=>required_text($data,'model',80),
        'model_year'=>integer_value(input($data,'model_year'),'Model year',1980,(int)date('Y')+1),
        'mileage'=>integer_value(input($data,'mileage'),'Mileage',0,2000000),
        'mot_expiry'=>valid_date(input($data,'mot_expiry'),'MOT expiry',true),
        'insurance_expiry'=>valid_date(input($data,'insurance_expiry'),'Insurance expiry',true),
        'next_service_date'=>valid_date(input($data,'next_service_date'),'Next service date',true),
        'next_service_mileage'=>$target === '' ? null : integer_value($target,'Next service mileage',0,2000000),
        'status'=>$status,
    ];
}
function driver_values(array $data): array {
    $email = strtolower(required_text($data,'email',190));
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid email address.');
    $phone = required_text($data,'phone',25);
    if (!preg_match('/^[+0-9() -]{7,25}$/D',$phone) || strlen(preg_replace('/\D/','',$phone) ?? '') < 7) throw new InvalidArgumentException('Enter a valid telephone number.');
    $licence = strtoupper(preg_replace('/\s+/','',required_text($data,'licence_number',20)) ?? '');
    if (!preg_match('/^[A-Z0-9]{5,20}$/D',$licence)) throw new InvalidArgumentException('Licence number must contain 5 to 20 letters or digits.');
    $active = input($data,'active');
    if (!in_array($active,['0','1'],true)) throw new InvalidArgumentException('Invalid driver status.');
    return ['name'=>required_text($data,'name',100),'email'=>$email,'phone'=>$phone,'licence_number'=>$licence,'licence_expiry'=>valid_date(input($data,'licence_expiry'),'Licence expiry'),'active'=>(int)$active];
}
function record_values(array $data, bool $service): array {
    $date = valid_date(input($data,'record_date'),'Record date');
    if ($date > date('Y-m-d')) throw new InvalidArgumentException('A completed maintenance record cannot be dated in the future.');
    $row = ['vehicle_id'=>integer_value(input($data,'vehicle_id'),'Vehicle',1,2147483647), ($service ? 'service_date' : 'repair_date')=>$date,
        'description'=>required_text($data,'description',2000),'supplier'=>required_text($data,'supplier',120),'cost_pence'=>to_pence(input($data,'cost'))];
    if ($service) $row['mileage'] = integer_value(input($data,'mileage'),'Service mileage',0,2000000);
    return $row;
}
