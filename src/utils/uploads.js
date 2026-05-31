import * as DocumentPicker from 'expo-document-picker';

export async function pickDocumentAsset(options = {}) {
  const result = await DocumentPicker.getDocumentAsync({
    copyToCacheDirectory: true,
    multiple: false,
    type: options.type || '*/*',
  });

  if (result.canceled || !result.assets?.length) {
    return null;
  }

  return result.assets[0];
}

export function appendFormDataField(formData, key, value) {
  if (value === undefined || value === null || value === '') {
    return;
  }

  formData.append(key, String(value));
}

export function createUploadFormData(fields = {}, fileFieldName = 'file', asset = null) {
  const formData = new FormData();

  Object.entries(fields).forEach(([key, value]) => {
    appendFormDataField(formData, key, value);
  });

  if (asset?.uri) {
    formData.append(fileFieldName, {
      uri: asset.uri,
      name: asset.name || `upload-${Date.now()}`,
      type: asset.mimeType || 'application/octet-stream',
    });
  }

  return formData;
}
