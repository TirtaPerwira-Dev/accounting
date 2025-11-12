# PROPOSAL: Chart of Account sebagai Grouping Layer

## Current State

Chart of Account masih menggunakan struktur legacy dengan backward compatibility ke SAKEP.

## Recommendation: Transform to Grouping Layer

### Use Cases:

1. **Custom Account Groups**: "Kas & Setara Kas", "Piutang Pelanggan", "Beban Operasional"
2. **Departmental Grouping**: "Akun Teknik", "Akun Keuangan", "Akun Komersial"
3. **Reporting Clusters**: "Akun untuk Laporan Bulanan", "Akun untuk Audit"
4. **Workflow Organization**: "Akun yang Perlu Approval", "Akun Auto-Post"

### New Structure:

```php
ChartOfAccount {
    id: 1,
    company_id: 1,
    code: "KAS-GROUP",
    name: "Kelompok Kas & Setara Kas",
    type: "asset",
    sakep_references: [
        nomor_bantu_id: [1, 2, 3], // Kas Besar, Kas Kecil, Bank
    ],
    is_active: true,
    created_by: user_id
}
```

### Benefits:

-   ✅ PDAM flexibility in organizing accounts
-   ✅ Better reporting and analytics
-   ✅ Maintain SAKEP compliance
-   ✅ Enhanced user experience
-   ✅ Future-proof for regulation changes

### Implementation:

1. Update ChartOfAccount model to support multiple SAKEP references
2. Create UI for managing account groups
3. Update reporting to use both SAKEP and groups
4. Add permissions for group management

Would you like me to implement this approach?
