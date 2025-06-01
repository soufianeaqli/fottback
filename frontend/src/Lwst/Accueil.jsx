import React from 'react';
import { Link } from 'react-router-dom';
import './accueil.css';

function Accueil({ user }) {
    return (
        <div className='edo'>
            <h1 className='re'>
                <span className="title-highlight">Réservez</span> Votre Terrain de Mini-Foot
            </h1>
            <p className='ree'>Profitez de nos terrains de qualité pour vos matchs entre amis ou en compétition.
                Réservation simple et rapide, disponibilité en temps réel.</p>
            <Link to={'/terrain'}>
                <button className='btn1'>
                    <i className="fas fa-futbol"></i> 
                    {user?.role === 'admin' ? 'Gérer les Terrains' : 'Réserver Maintenant'}
                </button>
            </Link>
        </div>
    );
}

export default Accueil;