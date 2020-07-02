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
  mensajeNotificacion: string;
  mostrarNotificacion: boolean = true;

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
    console.log(JSON.stringify(this.formularioResetClave.value));

    //Función para ver si el usuario está logueado
    this.CambiarClaveService.resetClave(
      this.formularioResetClave.value.email
    )
      .subscribe(
        (response) => {
          if (response.status == 1) {
            console.log(response.checkSendEmail);

            //Aquí ya se puede cerrar la página y verificar el email
            /*  this.router.navigate(['/home']); */
          } else {
            console.log(response.checkSendEmail);
            this.mensajeNotificacion = response.response;
            this.ocultarNotificacion(false);
          }
        },
        (error) => {
          console.log(error);
        }
      );
  }

  //Función cambiar estado notificación
  ocultarNotificacion(value: boolean) {
    this.mostrarNotificacion = value;
  }

}
