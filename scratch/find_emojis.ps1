$matches = Select-String -Path "resources\js\Pages\Tenant\Admin\Dashboard.vue" -Pattern '[^\x00-\x7F]'
$matches | ForEach-Object { $_.LineNumber.ToString() + ": " + $_.Line.Trim() } | Out-File -FilePath "scratch\admin_emojis.txt" -Encoding utf8
