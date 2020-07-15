import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';

@Component({
  selector: 'app-notificaciones',
  templateUrl: './notificaciones.component.html',
  styleUrls: ['./notificaciones.component.scss']
})
export class NotificacionesComponent implements OnInit {

  constructor() { }

  @Input() statusNotificacion: string; //error o success
  @Input() mensajeNotificacion: string; //mensaje de error o éxito
  @Input() iconoNotificacion: string; //Icono de éxito o error

  @Output() mostrarNotificacion = new EventEmitter<boolean>();

  ngOnInit(): void { }

  ocultarNotificacion(value: boolean) {
    this.mostrarNotificacion.emit(value);
  }

}
