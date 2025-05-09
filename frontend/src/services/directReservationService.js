/**
 * Service pour la gestion des réservations via les scripts PHP directs
 */

const API_BASE_URL = 'http://127.0.0.1:8000';

/**
 * Récupère toutes les réservations
 * @returns {Promise} Promesse contenant les données des réservations
 */
export const getAllReservations = async () => {
  try {
    console.log('Chargement de toutes les réservations via script direct');
    const response = await fetch(`${API_BASE_URL}/direct-get-reservations.php`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Erreur lors du chargement des réservations: ${response.status} ${errorText}`);
    }

    const data = await response.json();
    console.log('Réservations chargées avec succès:', data);
    return { success: true, data: data };
  } catch (error) {
    console.error('Erreur dans getAllReservations:', error);
    return { success: false, error: error.message, data: [] };
  }
};

/**
 * Récupère les réservations d'un utilisateur
 * @param {string} username Nom d'utilisateur
 * @returns {Promise} Promesse contenant les données des réservations
 */
export const getUserReservations = async (username) => {
  try {
    console.log(`Chargement des réservations pour l'utilisateur ${username} via script direct`);
    const response = await fetch(`${API_BASE_URL}/direct-get-user-reservations.php?user_id=${username}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Erreur lors du chargement des réservations: ${response.status} ${errorText}`);
    }

    const data = await response.json();
    console.log('Réservations utilisateur chargées avec succès:', data);
    return { success: true, data: data };
  } catch (error) {
    console.error('Erreur dans getUserReservations:', error);
    return { success: false, error: error.message, data: [] };
  }
};

/**
 * Crée une nouvelle réservation
 * @param {Object} reservationData Données de la réservation
 * @returns {Promise} Promesse contenant les données de la réservation créée
 */
export const createReservation = async (reservationData) => {
  try {
    console.log('Création d\'une réservation via script direct:', reservationData);
    
    // Convertir les données pour correspondre au format attendu par le backend
    const dataToSend = {
      ...reservationData,
      // Utiliser à la fois price et prix pour assurer la compatibilité
      prix: typeof reservationData.price === 'number' ? 
        reservationData.price : 
        (parseFloat(reservationData.price) || 0)
    };
    
    console.log('Données formatées pour l\'envoi:', dataToSend);
    
    const response = await fetch(`${API_BASE_URL}/direct-create-reservation.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(dataToSend)
    });

    console.log('Statut de la réponse:', response.status);
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error('Réponse d\'erreur reçue:', errorText);
      throw new Error(`Erreur lors de la création de la réservation: ${response.status} ${errorText}`);
    }

    const result = await response.json();
    console.log('Données de réponse:', result);
    
    if (!result.success) {
      throw new Error(result.message || 'Erreur lors de la création de la réservation');
    }

    console.log('Réservation créée avec succès:', result.data);
    return { success: true, data: result.data };
  } catch (error) {
    console.error('Erreur dans createReservation:', error);
    return { success: false, error: error.message };
  }
};

/**
 * Met à jour une réservation existante
 * @param {number} id ID de la réservation
 * @param {Object} reservationData Données de la réservation à mettre à jour
 * @returns {Promise} Promesse contenant les données de la réservation mise à jour
 */
export const updateReservation = async (id, reservationData) => {
  try {
    console.log(`Mise à jour de la réservation ${id} via script direct:`, reservationData);
    const response = await fetch(`${API_BASE_URL}/direct-update-reservation.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ ...reservationData, id })
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Erreur lors de la mise à jour de la réservation: ${response.status} ${errorText}`);
    }

    const result = await response.json();
    if (!result.success) {
      throw new Error(result.message || 'Erreur lors de la mise à jour de la réservation');
    }

    console.log('Réservation mise à jour avec succès:', result.data);
    return { success: true, data: result.data };
  } catch (error) {
    console.error('Erreur dans updateReservation:', error);
    return { success: false, error: error.message };
  }
};

/**
 * Supprime une réservation
 * @param {number} id ID de la réservation
 * @param {string} userId ID de l'utilisateur (pour vérification)
 * @param {boolean} isAdmin Indique si l'utilisateur est un administrateur
 * @returns {Promise} Promesse contenant le résultat de la suppression
 */
export const deleteReservation = async (id, userId = null, isAdmin = false) => {
  try {
    console.log(`Suppression de la réservation ${id} via script direct (admin: ${isAdmin})`);
    let url = `${API_BASE_URL}/direct-delete-reservation.php?id=${id}`;
    
    if (userId) {
      url += `&user_id=${userId}`;
    }
    
    if (isAdmin) {
      url += `&is_admin=true`;
    }
    
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Erreur lors de la suppression de la réservation: ${response.status} ${errorText}`);
    }

    const result = await response.json();
    if (!result.success) {
      throw new Error(result.message || 'Erreur lors de la suppression de la réservation');
    }

    console.log('Réservation supprimée avec succès:', result);
    return { success: true, data: result.data };
  } catch (error) {
    console.error('Erreur dans deleteReservation:', error);
    return { success: false, error: error.message };
  }
};

/**
 * Vérifie la disponibilité d'un créneau
 * @param {number} terrainId ID du terrain
 * @param {string} date Date au format YYYY-MM-DD
 * @param {string} timeSlot Créneau horaire (ex: "09:00-10:00")
 * @returns {Promise} Promesse contenant le résultat de la vérification
 */
export const checkAvailability = async (terrainId, date, timeSlot) => {
  try {
    console.log(`Vérification de la disponibilité pour le terrain ${terrainId}, date ${date}, créneau ${timeSlot}`);
    const response = await fetch(`${API_BASE_URL}/direct-check-availability.php?terrain_id=${terrainId}&date=${date}&time_slot=${timeSlot}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Erreur lors de la vérification de disponibilité: ${response.status} ${errorText}`);
    }

    const result = await response.json();
    console.log('Résultat de la vérification de disponibilité:', result);
    return { 
      success: result.success, 
      data: { available: result.available, message: result.message }
    };
  } catch (error) {
    console.error('Erreur dans checkAvailability:', error);
    return { success: false, error: error.message, data: { available: false } };
  }
};

/**
 * Marque une réservation comme payée
 * @param {number} id ID de la réservation
 * @param {string} userId ID de l'utilisateur (pour vérification)
 * @returns {Promise} Promesse contenant le résultat de l'opération
 */
export const markAsPaid = async (id, userId = null) => {
  try {
    console.log(`Marquage de la réservation ${id} comme payée via script direct`);
    let url = `${API_BASE_URL}/direct-mark-paid.php?id=${id}`;
    if (userId) {
      url += `&user_id=${userId}`;
    }
    
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Erreur lors du marquage comme payée: ${response.status} ${errorText}`);
    }

    const result = await response.json();
    if (!result.success) {
      throw new Error(result.message || 'Erreur lors du marquage comme payée');
    }

    console.log('Réservation marquée comme payée avec succès:', result);
    return { success: true, data: result.data };
  } catch (error) {
    console.error('Erreur dans markAsPaid:', error);
    return { success: false, error: error.message };
  }
}; 