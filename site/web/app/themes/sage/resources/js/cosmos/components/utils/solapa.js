export function solapaContentAbrir(epigrafe, nombre) {
  const text1Element = document.getElementById('epig');
  const text2Element = document.getElementById('nomb');
  const solapaElement = document.getElementById('solapa');
  solapaElement.classList.remove('opacity-0');

  text1Element.innerHTML = epigrafe;
  text2Element.innerHTML = nombre;
}

export function solapaContentCerrar() {
  const solapaElement = document.getElementById('solapa');
  solapaElement.classList.add('opacity-0');
}
