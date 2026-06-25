param(
    [Parameter(Mandatory = $true)]
    [string]$SourceFolder
)

$ErrorActionPreference = 'Stop'

function ConvertTo-SqlLiteral {
    param([object]$Value)

    if ($null -eq $Value) {
        return 'NULL'
    }

    $text = [string]$Value
    if ([string]::IsNullOrWhiteSpace($text)) {
        return 'NULL'
    }

    $escaped = $text.Replace("'", "''")
    return "'$escaped'"
}

function New-ValuesClause {
    param(
        [object[]]$Rows,
        [string[]]$Columns,
        [hashtable]$ExtraValues = @{}
    )

    $rows = foreach ($row in $Rows) {
        $values = foreach ($column in $Columns) {
            if ($ExtraValues.ContainsKey($column)) {
                ConvertTo-SqlLiteral $ExtraValues[$column]
            } else {
                ConvertTo-SqlLiteral $row.$column
            }
        }

        '(' + ($values -join ', ') + ')'
    }

    return $rows -join ",`r`n"
}

function Write-SeedBlock {
    param(
        [string]$Table,
        [string[]]$Columns,
        [object[]]$Rows,
        [string[]]$UpdateColumns,
        [hashtable]$ExtraValues = @{}
    )

    if (-not $Rows -or $Rows.Count -eq 0) {
        return
    }

    $valuesClause = New-ValuesClause -Rows $Rows -Columns $Columns -ExtraValues $ExtraValues
    $updateClause = ($UpdateColumns | ForEach-Object { "$_ = VALUES($_)" }) -join ",`r`n    "

    @"
INSERT INTO $Table ($($Columns -join ', '))
VALUES
$valuesClause
ON DUPLICATE KEY UPDATE
    $updateClause;

"@ | Add-Content -Path $script:OutputPath -Encoding utf8
}

$script:OutputPath = Join-Path (Split-Path -Parent $PSScriptRoot) 'docker\mysql\init\02-seeds.sql'

Remove-Item -LiteralPath $script:OutputPath -ErrorAction SilentlyContinue

"-- Generated from CSV files in $SourceFolder" | Set-Content -Path $script:OutputPath -Encoding utf8
"SET FOREIGN_KEY_CHECKS = 0;" | Add-Content -Path $script:OutputPath -Encoding utf8
"" | Add-Content -Path $script:OutputPath -Encoding utf8

$users = Import-Csv (Join-Path $SourceFolder 'users.csv') | Sort-Object { [int]$_.user_id }
$vehicles = Import-Csv (Join-Path $SourceFolder 'vehicles.csv') | Sort-Object { [int]$_.vehicle_id }
$compliance = Import-Csv (Join-Path $SourceFolder 'compliance_records.csv') | Sort-Object { [int]$_.compliance_id }
$services = Import-Csv (Join-Path $SourceFolder 'service_records.csv') | Sort-Object { [int]$_.service_id }
$notifications = Import-Csv (Join-Path $SourceFolder 'notifications.csv') | Sort-Object { [int]$_.notification_id }

Write-SeedBlock `
    -Table 'users' `
    -Columns @('user_id', 'name', 'email', 'role', 'password_hash') `
    -Rows $users `
    -UpdateColumns @('name', 'email', 'role', 'password_hash') `
    -ExtraValues @{ password_hash = $null }

$vehicleRows = foreach ($row in $vehicles) {
    [pscustomobject]@{
        vehicle_id           = $row.vehicle_id
        owner_id             = $row.owner_id
        plate_number         = $row.plate_no
        make                 = $row.make
        model                = $row.model
        year                 = $row.year
        inspection_status    = 'Pending Police Check'
        inspection_checked_at = $null
    }
}

Write-SeedBlock `
    -Table 'vehicles' `
    -Columns @('vehicle_id', 'owner_id', 'plate_number', 'make', 'model', 'year', 'inspection_status', 'inspection_checked_at') `
    -Rows $vehicleRows `
    -UpdateColumns @('owner_id', 'plate_number', 'make', 'model', 'year', 'inspection_status', 'inspection_checked_at')

Write-SeedBlock `
    -Table 'compliance_records' `
    -Columns @('compliance_id', 'vehicle_id', 'insurance_expiry', 'insurance_status', 'licence_expiry', 'licence_status', 'registration_expiry', 'registration_status') `
    -Rows $compliance `
    -UpdateColumns @('vehicle_id', 'insurance_expiry', 'insurance_status', 'licence_expiry', 'licence_status', 'registration_expiry', 'registration_status')

Write-SeedBlock `
    -Table 'service_records' `
    -Columns @('service_id', 'vehicle_id', 'service_details', 'last_service_date', 'next_service_date') `
    -Rows $services `
    -UpdateColumns @('vehicle_id', 'service_details', 'last_service_date', 'next_service_date')

Write-SeedBlock `
    -Table 'notifications' `
    -Columns @('notification_id', 'user_id', 'notification_type', 'message', 'status', 'date_sent') `
    -Rows $notifications `
    -UpdateColumns @('user_id', 'notification_type', 'message', 'status', 'date_sent')

"SET FOREIGN_KEY_CHECKS = 1;" | Add-Content -Path $script:OutputPath -Encoding utf8
