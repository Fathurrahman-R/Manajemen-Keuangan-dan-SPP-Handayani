@livewire('import-preview-table', [
    'importType' => $importType,
    'previewId' => $previewId,
    'preview' => $preview,
], key('import-preview-'.($previewId ?? 'kosong')))
