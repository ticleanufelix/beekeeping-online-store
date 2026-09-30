import { Injectable } from '@angular/core';
import { Article } from '../models/article';

@Injectable({
  providedIn: 'root',
})
export class ArticleService {
  private readonly articles: Article[] = [
    {
      id: 1,
      slug: 'de-ce-se-cristalizeaza-mierea',
      title: 'De ce se cristalizează mierea?',
      summary: 'Descoperă de ce mierea își schimbă textura și ce influențează cristalizarea.',
      content:
        'Cristalizarea este un proces natural prin care mierea trece de la o consistență lichidă la una mai densă. Glucoza formează cristale, iar textura mierii se schimbă treptat.\n\nViteza acestui proces diferă în funcție de compoziția mierii și de condițiile de păstrare. Unele sortimente cristalizează repede, iar altele rămân lichide mai mult timp.\n\nCristalizarea, luată singură, nu dovedește nici autenticitatea, nici calitatea unei mieri.',
      image: '/images/categorie-miere.png',
      category: 'Miere',
      published: true,
    },
    {
      id: 2,
      slug: 'cum-pastram-mierea-acasa',
      title: 'Cum păstrăm mierea acasă?',
      summary: 'Câteva obiceiuri simple pentru păstrarea borcanelor cu miere.',
      content:
        'Păstrează mierea într-un recipient bine închis, într-un loc uscat, ferit de lumina directă a soarelui și de sursele de căldură.\n\nFolosește o lingură curată și uscată atunci când iei miere din borcan. Închide capacul după utilizare pentru a limita contactul cu umezeala și mirosurile din jur.\n\nRespectă indicațiile de păstrare și termenul înscrise pe eticheta produsului.',
      image: '/images/categorie-miere.png',
      category: 'Sfaturi',
      published: true,
    },
    {
      id: 3,
      slug: 'rolul-albinelor-in-natura',
      title: 'Rolul albinelor în natură',
      summary: 'Albinele contribuie la polenizare și fac parte din viața multor ecosisteme.',
      content:
        'Atunci când vizitează florile pentru nectar și polen, albinele pot transporta polenul de la o floare la alta. Acest proces contribuie la reproducerea multor plante.\n\nAlbina meliferă este doar una dintre numeroasele specii de albine. Albinele sălbatice și alte insecte au, de asemenea, un rol în polenizare.\n\nSpațiile cu plante variate și perioade diferite de înflorire pot oferi hrană polenizatorilor de-a lungul sezonului.',
      image: '/images/categorie-ingrijire.png',
      category: 'Apicultură',
      published: true,
    },
    {
      id: 4,
      slug: 'primii-pasi-in-apicultura',
      title: 'Primii pași în apicultură',
      summary: 'De unde poți începe dacă vrei să descoperi activitatea dintr-o stupină.',
      content:
        'Înainte să cumperi stupi, începe prin a înțelege cum funcționează o familie de albine și ce presupune îngrijirea ei pe parcursul anului.\n\nUn curs introductiv și lucrul alături de un apicultor cu experiență te pot ajuta să legi teoria de practica din stupină. Pregătește echipamentul de protecție și informează-te despre amplasarea stupilor și obligațiile aplicabile.\n\nÎncepe cu un număr de familii pe care îl poți gestiona și păstrează un jurnal al observațiilor.',
      image: '/images/categorie-stupi.png',
      category: 'Apicultură',
      published: true,
    },
    {
      id: 5,
      slug: 'ce-gasim-in-interiorul-unui-stup',
      title: 'Ce găsim în interiorul unui stup?',
      summary: 'O introducere în organizarea familiei de albine și a fagurilor.',
      content:
        'Într-un stup se află familia de albine și fagurii pe care aceasta îi construiește și îi folosește. Familia include matca, albinele lucrătoare și, în anumite perioade, trântorii.\n\nCelulele fagurilor pot conține puiet, miere sau polen. Distribuția lor se schimbă în funcție de sezon și de starea familiei.\n\nLa inspecție, apicultorul urmărește aceste elemente pentru a înțelege evoluția familiei și nevoile ei.',
      image: '/images/categorie-stupi.png',
      category: 'Apicultură',
      published: true,
    },
    {
      id: 6,
      slug: 'ustensile-utile-in-stupina',
      title: 'Ustensile utile în stupină',
      summary: 'Descoperă câteva dintre uneltele folosite în activitatea apicolă.',
      content:
        'Echipamentul de protecție, dalta apicolă și afumătorul sunt printre obiectele întâlnite frecvent într-o stupină.\n\nDalta ajută la desprinderea și manipularea elementelor stupului. Afumătorul se folosește cu atenție, având grijă la materialul ars și la riscul de incendiu.\n\nAlegerea ustensilelor depinde de tipul stupilor și de activitățile desfășurate. Curățarea și întreținerea lor fac parte din rutina de lucru.',
      image: '/images/categorie-ustensile.png',
      category: 'Sfaturi',
      published: true,
    },
    {
      id: 7,
      slug: 'cum-alegem-mierea',
      title: 'Cum alegem mierea?',
      summary: 'Ce informații merită să citim pe etichetă înainte de cumpărare.',
      content:
        'Atunci când alegi mierea, citește eticheta și urmărește informațiile despre origine, cantitate, termen și condițiile de păstrare.\n\nSortimentele pot avea culori, arome și consistențe diferite. Aceste caracteristici sunt influențate de sursele florale și de evoluția mierii după recoltare.\n\nCuloarea, consistența și testele improvizate acasă nu sunt suficiente pentru a stabili autenticitatea produsului. Alege un furnizor care poate oferi informații clare despre mierea comercializată.',
      image: '/images/categorie-miere.png',
      category: 'Miere',
      published: true,
    },
    {
      id: 8,
      slug: 'jurnalul-apicultorului',
      title: 'Jurnalul apicultorului',
      summary: 'Cum te pot ajuta notițele să urmărești activitatea din stupină.',
      content:
        'Un jurnal al stupinei te ajută să păstrezi observațiile făcute la fiecare inspecție și să urmărești schimbările în timp.\n\nPoți nota data, condițiile meteo, observațiile despre puiet și rezerve, precum și lucrările efectuate. Identificarea fiecărui stup permite compararea notițelor de la o vizită la alta.\n\nUn format simplu, folosit consecvent, este mai util decât un jurnal complicat pe care îl completezi rar.',
      image: '/images/categorie-stupi.png',
      category: 'Sfaturi',
      published: false,
    },
  ];

  getAll(): Article[] {
    return this.articles.filter((article) => article.published);
  }

  getBySlug(slug: string): Article | undefined {
    return this.articles.find((article) => article.slug === slug && article.published);
  }

  getByCategory(category: string): Article[] {
    return this.articles.filter((article) => article.category === category && article.published);
  }
}
