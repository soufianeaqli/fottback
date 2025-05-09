import { Route, Routes, Navigate } from "react-router-dom";
import React, { useState, useEffect } from "react";
import Header from "./Header/Header";
import Accueil from "./Lwst/Accueil";
import Terrain from "./Lwst/Terrain";
import TerrainDetail from "./Lwst/TerrainDetail";
import Reservation from "./Lwst/Reservation";
import Contact from "./Lwst/Contact";
import Tournoi from './Lwst/Tournoi';
import Login from './Lwst/Login';
import LoginPrompt from './Lwst/LoginPrompt';
import TournoiDetail from './Lwst/TournoiDetail';
import Parametres from './Lwst/Parametres';
import "./lwst/lwst.css";
import * as reservationService from "./services/reservationService";



function App() {
  const [reservations, setReservations] = useState(() => {
    const saved = localStorage.getItem('allReservations');
    return saved ? JSON.parse(saved) : [];
  });
  const [user, setUser] = useState(null);
  const [terrains, setTerrains] = useState([]);

  const [tournois, setTournois] = useState(() => {
    const saved = localStorage.getItem('tournois');
    if (saved) {
      return JSON.parse(saved);
    }
    return [
      {
        id: 1,
        name: "Coupe de la Ville",
        date: "2024-04-15",
        maxTeams: 16,
        registeredTeams: 8,
        prizePool: "10000 DH",
        description: "Tournoi annuel opposant les meilleures équipes",
        format: "Élimination directe",
        entryFee: "500 DH"
      },
      {
        id: 2,
        name: "Championnat Amateur",
        date: "2024-05-01",
        maxTeams: 12,
        registeredTeams: 6,
        prizePool: "5000 DH",
        description: "Tournoi réservé aux équipes amateurs",
        format: "Phase de groupes + Élimination directe",
        entryFee: "300 DH"
      },
      {
        id: 3,
        name: "Tournoi Ramadan",
        date: "2024-03-20",
        maxTeams: 20,
        registeredTeams: 12,
        prizePool: "15000 DH",
        description: "Grand tournoi nocturne pendant le mois de Ramadan",
        format: "Phase de groupes + Élimination directe",
        entryFee: "600 DH"
      }
    ];
  });

  useEffect(() => {
    localStorage.setItem('tournois', JSON.stringify(tournois));
  }, [tournois]);

  useEffect(() => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      setUser(JSON.parse(storedUser));
    }
  }, []);

  useEffect(() => {
    localStorage.setItem('allReservations', JSON.stringify(reservations));
  }, [reservations]);

  // Charger les terrains depuis l'API
  useEffect(() => {
    const fetchTerrains = async () => {
      try {
        // Utiliser le script PHP direct sans CSRF
        const response = await fetch('http://127.0.0.1:8000/direct-get-terrains.php', {
          method: 'GET',
          headers: {
            'Accept': 'application/json'
          }
        });

        if (response.ok) {
          const data = await response.json();
          console.log('App - Terrains chargés avec succès:', data);
          setTerrains(data);
        } else {
          throw new Error('Erreur lors du chargement des terrains');
        }
      } catch (error) {
        console.error('Erreur lors du chargement des terrains:', error);
      }
    };
    fetchTerrains();
  }, []);

  const addReservation = (newReservation) => {
    const reservationWithUser = {
      ...newReservation,
      userId: user ? user.username : 'guest',
      accepted: user?.role === 'admin' ? true : false,
      id: Date.now().toString()
    };

    const updatedReservations = [...reservations, reservationWithUser];
    setReservations(updatedReservations);
    localStorage.setItem('allReservations', JSON.stringify(updatedReservations));
    return reservationWithUser;
  };

  const modifyReservation = (updatedReservation) => {
    const updatedReservations = reservations.map(reservation =>
      reservation.id === updatedReservation.id ? updatedReservation : reservation
    );
    setReservations(updatedReservations);
    localStorage.setItem('allReservations', JSON.stringify(updatedReservations));
  };

  const deleteReservation = (id) => {
    const updatedReservations = reservations.filter(reservation => reservation.id !== id);
    setReservations(updatedReservations);
    localStorage.setItem('allReservations', JSON.stringify(updatedReservations));
  };

  const acceptReservation = (id) => {
    const updatedReservations = reservations.map(reservation =>
      reservation.id === id ? { ...reservation, accepted: true } : reservation
    );
    setReservations(updatedReservations);
    localStorage.setItem('allReservations', JSON.stringify(updatedReservations));
  };

  const handleLogin = (userData) => {
    setUser(userData);
    localStorage.setItem('user', JSON.stringify(userData));
  };

  const handleLogout = () => {
    setUser(null);
    localStorage.removeItem('user');
  };

  return (
   
      <div className="app-container">
        {/* Toast notifications */}
      
        
        {/* Vidéo en arrière-plan */}
        <div className="video-background">
          <video autoPlay loop muted playsInline>
            <source src="/foot.mp4" type="video/mp4" />
            Votre navigateur ne supporte pas la vidéo.
          </video>
        </div>
        <div className="video-overlay"></div>

        {/* Contenu du site */}
        <Header user={user} logout={handleLogout} />
        <Routes>
          <Route path="/" element={<Navigate to="/accueil" />} />
          <Route path="accueil" element={<Accueil user={user} />} />
          <Route path="terrain" element={
            <Terrain 
              user={user} 
              addReservation={addReservation} 
              reservations={reservations}
            />
          } />
          <Route path="terrain/:id" element={
            <TerrainDetail 
              user={user}
              addReservation={addReservation} 
              reservations={reservations}
              terrains={terrains}
            />
          } />
          <Route path="reservation" element={user ? <Reservation user={user} reservations={reservations} deleteReservation={deleteReservation} modifyReservation={modifyReservation} acceptReservation={acceptReservation} /> : <Navigate to="/login" />} />
          <Route path="contact" element={<Contact user={user} />} />
          <Route path="tournoi" element={<Tournoi user={user} tournois={tournois} setTournois={setTournois} />} />
          <Route path="tournoi/:id" element={<TournoiDetail user={user} tournois={tournois} setTournois={setTournois} />} />
          <Route path="login" element={<Login setUser={handleLogin} />} />
          <Route path="parametres" element={user ? <Parametres user={user} setUser={setUser} /> : <Navigate to="/login" />} />
        </Routes>
      </div>
   
  );
}

export default App;