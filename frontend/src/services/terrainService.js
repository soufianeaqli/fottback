/**
 * Service pour la gestion des terrains via les scripts PHP directs
 */

const API_BASE_URL = 'http://127.0.0.1:8000';

/**
 * Récupère tous les terrains
 * @returns {Promise} Promesse contenant les données des terrains
 */
export const getAllTerrains = async () => {
  try {
    console.log('Chargement des terrains via script direct');
    const response = await fetch(`${API_BASE_URL}/direct-get-terrains.php`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Erreur lors du chargement des terrains: ${response.status} ${errorText}`);
    }

    const data = await response.json();
    console.log('Terrains chargés avec succès:', data);
    return { success: true, data };
  } catch (error) {
    console.error('Erreur dans getAllTerrains:', error);
    return { success: false, error: error.message };
  }
};

/**
 * Récupère un terrain spécifique par son ID
 * @param {number} id ID du terrain
 * @returns {Promise} Promesse contenant les données du terrain
 */
export const getTerrain = async (id) => {
  try {
    console.log(`Chargement du terrain ${id} via script direct`);
    const response = await fetch(`${API_BASE_URL}/direct-get-terrain.php?id=${id}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Erreur lors du chargement du terrain: ${response.status} ${errorText}`);
    }

    const data = await response.json();
    console.log('Terrain chargé avec succès:', data);
    return { success: true, data };
  } catch (error) {
    console.error('Erreur dans getTerrain:', error);
    return { success: false, error: error.message };
  }
};

/**
 * Ajoute un nouveau terrain
 * @param {Object} terrainData Données du terrain à ajouter
 * @returns {Promise} Promesse contenant le résultat de l'opération
 */
export const addTerrain = async (terrainData) => {
  try {
    console.log('Ajout d\'un terrain via script direct:', terrainData);
    const formData = new FormData();
    
    // Ajouter les données du terrain au FormData
    Object.keys(terrainData).forEach(key => {
      formData.append(key, terrainData[key]);
    });

    const response = await fetch(`${API_BASE_URL}/direct-add.php`, {
      method: 'POST',
      body: formData
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Erreur lors de l'ajout du terrain: ${response.status} ${errorText}`);
    }

    const data = await response.json();
    console.log('Terrain ajouté avec succès:', data);
    return { success: true, data };
  } catch (error) {
    console.error('Erreur dans addTerrain:', error);
    return { success: false, error: error.message };
  }
};

/**
 * Supprime un terrain spécifique
 * @param {number} id ID du terrain
 * @returns {Promise} Promesse contenant le résultat de l'opération
 */
export const deleteTerrain = async (id) => {
  try {
    console.log(`Suppression du terrain ${id} via script direct`);
    const response = await fetch(`${API_BASE_URL}/direct-delete.php?id=${id}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Erreur lors de la suppression du terrain: ${response.status} ${errorText}`);
    }

    const data = await response.json();
    console.log('Terrain supprimé avec succès:', data);
    return { success: true, data };
  } catch (error) {
    console.error('Erreur dans deleteTerrain:', error);
    return { success: false, error: error.message };
  }
};

/**
 * Télécharge une image
 * @param {File} file Fichier image à télécharger
 * @returns {Promise} Promesse contenant le résultat de l'opération
 */
export const uploadImage = async (file) => {
  try {
    console.log('Téléchargement d\'image via script direct:', file.name);
    
    // Vérifier le type de fichier
    if (!['image/jpeg', 'image/png'].includes(file.type)) {
      throw new Error('Type de fichier non supporté. Utilisez JPG ou PNG.');
    }
    
    // Vérifier la taille du fichier (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      throw new Error('Fichier trop volumineux. Taille maximum: 5MB.');
    }
    
    const formData = new FormData();
    formData.append('image', file);

    const response = await fetch(`${API_BASE_URL}/direct-upload.php`, {
      method: 'POST',
      body: formData
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Erreur lors du téléchargement de l'image: ${response.status} ${errorText}`);
    }

    const data = await response.json();
    console.log('Image téléchargée avec succès:', data);
    return { success: true, data };
  } catch (error) {
    console.error('Erreur dans uploadImage:', error);
    return { success: false, error: error.message };
  }
}; 