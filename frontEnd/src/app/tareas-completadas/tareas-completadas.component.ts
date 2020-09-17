import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-tareas-completadas',
  templateUrl: './tareas-completadas.component.html',
  styleUrls: ['./tareas-completadas.component.scss']
})
export class TareasCompletadasComponent implements OnInit {

  constructor(public router: Router) { }

  ngOnInit(): void {
  }

}
