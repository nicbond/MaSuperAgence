import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

export default class Map {

  static init () {
    let map = document.querySelector('#map')
    if (map === null) {
      return
    }
    let icon = L.icon({
      iconUrl: '/images/properties/marker-icon.png',
    })
    let center = [map.dataset.lat, map.dataset.lng]
    map = L.map('map').setView(center, 13)

    L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
      maxZoom: 18,
      minZoom: 12,
      attribution: '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map)
    L.marker(center, {icon: icon}).addTo(map)
  }

}
