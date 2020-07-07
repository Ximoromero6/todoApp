import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.scss']
})
export class HomeComponent implements OnInit {

  constructor(
    private router: Router
  ) { }

  ngOnInit(): void {
  }

  cerrarSesion() {
    alert('Works');
    localStorage.removeItem('userData');
    sessionStorage.removeItem('userData');
    this.router.navigate(['']);
  }

}
