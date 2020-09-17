import { Component, OnInit } from '@angular/core';
import { CambiarClaveService } from './cambiar-clave.service';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';

@Component({
  selector: 'app-cambiar-clave',
  templateUrl: './cambiar-clave.component.html',
  styleUrls: ['./cambiar-clave.component.scss']
})
export class CambiarClaveComponent implements OnInit {

  formularioResetClave: FormGroup;

  //Notificaciones variables
  statusNotificacion: string;
  iconoNotificacion: string;
  mensajeNotificacion: string;
  mostrarNotificacion: boolean = true;

  loading: boolean = false;

  constructor(
    private formBuilder: FormBuilder,
    private CambiarClaveService: CambiarClaveService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.formularioResetClave = this.formBuilder.group({
      email: ['', Validators.required]
    });
  }

  //Función para cambiar contraseña
  validarDatosResetClave() {
    if (this.formularioResetClave.invalid) {
      this.formularioResetClave.get('email').markAsDirty();
      return;
    }

    this.loading = true;
    (document.getElementById('sendLoading') as HTMLInputElement).disabled = true;
    //Función para ver si el usuario está logueado
    this.CambiarClaveService.resetClave(
      this.formularioResetClave.value.email
    )
      .subscribe(
        (response) => {
          if (response.status) {
            this.statusNotificacion = 'success';
            this.iconoNotificacion = 'fas fa-check-circle';
            this.mensajeNotificacion = response.response;
            this.formularioResetClave.reset();
            this.loading = false;
          } else {
            this.statusNotificacion = 'error';
            this.iconoNotificacion = 'fas fa-exclamation-circle';
            this.mensajeNotificacion = response.response;
            this.loading = false;
            (document.getElementById('sendLoading') as HTMLInputElement).disabled = false;
          }
          this.ocultarNotificacion(false);
          setTimeout(() => {
            this.ocultarNotificacion(true);
          }, 5000);
        },
        (error) => {
          this.loading = false;
          console.log(error);
        }
      );
  }

  //Función cambiar estado notificación
  ocultarNotificacion(value: boolean) {
    this.mostrarNotificacion = value;
  }

}
